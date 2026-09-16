<?php

namespace App\Http\Controllers\Api;

use App\Events\CarAvailabilityChanged;
use App\Events\ReservationStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Car;
use App\Models\Reservation;
use App\Services\CarAvailabilityService;
use App\Services\NotificationService;
use App\Services\ReservationStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Supports client-owned requests and staff workflow actions. */
class ReservationController extends Controller
{
    /** Receives the booking, workflow, and notification services used by reservation actions. */
    public function __construct(private CarAvailabilityService $availability, private ReservationStatusService $statuses, private NotificationService $notifications) {}

    /** Lists only the authenticated client's reservations and payment summaries. */
    public function mine(Request $request)
    {
        return ReservationResource::collection($request->user()->reservations()->with(['car.images', 'payments'])->latest()->paginate(10));
    }

    /** Creates a server-priced pending hold inside a row-locked database transaction. */
    public function store(StoreReservationRequest $request): ReservationResource
    {
        $reservation = DB::transaction(function () use ($request): Reservation {
            // Lock the selected car so two clients cannot pass the final check together.
            $car = Car::query()->lockForUpdate()->findOrFail($request->integer('car_id'));
            // Check overlap again while the lock is held and calculate the total on the server.
            $quote = $this->availability->quote($car, (string) $request->start_date, (string) $request->end_date, lock: true);
            abort_unless(
                $quote['available'],
                409,
                'Cette voiture est déjà réservée ou temporairement bloquée pour tout ou partie de cette période. Veuillez choisir d’autres dates.'
            );

            return Reservation::create([
                'reservation_number' => 'AST-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'car_id' => $car->id,
                'start_date' => $quote['start_date'],
                'end_date' => $quote['end_date'],
                'rental_days' => $quote['rental_days'],
                'daily_price' => $quote['daily_price'],
                'total_amount' => $quote['total_amount'],
                'status' => Reservation::STATUS_PENDING,
                'client_message' => $request->client_message,
            ]);
        }, 3);
        // Reverb transport failures never undo a valid business write; the UI
        // has focus/visibility/polling fallbacks for this exact condition.
        rescue(fn () => event(new ReservationStatusChanged($reservation)), report: true);
        rescue(fn () => event(new CarAvailabilityChanged($reservation, 'unavailable')), report: true);
        $this->notifications->staff('reservation.created', 'Nouvelle réservation', 'La réservation '.$reservation->reservation_number.' attend votre validation.', ['reservation_id' => $reservation->id, 'status' => 'pending']);

        return new ReservationResource($reservation->load(['car.images', 'payments']));
    }

    /** Returns one reservation only when it belongs to the authenticated client. */
    public function showMine(Request $request, Reservation $reservation): ReservationResource
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return new ReservationResource($reservation->load(['car.images', 'payments']));
    }

    /** Lets a client cancel only their own still-pending request. */
    public function cancelMine(Request $request, Reservation $reservation): ReservationResource
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless($reservation->status === Reservation::STATUS_PENDING, 422, 'Seule une demande en attente peut être annulée.');

        return new ReservationResource($this->statuses->transition($reservation, Reservation::STATUS_CANCELLED));
    }

    /** Lists all reservations for authorized operational staff. */
    public function staffIndex(Request $request)
    {
        return ReservationResource::collection(Reservation::with(['car.images', 'user', 'payments'])->when($request->status, fn ($query, $value) => $query->where('status', $value))->latest()->paginate(15));
    }

    /** Returns a complete reservation record to authorized staff. */
    public function staffShow(Reservation $reservation): ReservationResource
    {
        return new ReservationResource($reservation->load(['car.images', 'user', 'payments']));
    }

    /** Validates an optional internal note and delegates the state transition. */
    public function transition(Request $request, Reservation $reservation, string $target): ReservationResource
    {
        $data = $request->validate(['internal_note' => ['nullable', 'string', 'max:2000']]);

        return new ReservationResource($this->statuses->transition($reservation, $target, $data['internal_note'] ?? null));
    }

    /** Confirms a pending reservation after the service repeats conflict protection. */
    public function confirm(Request $request, Reservation $reservation): ReservationResource
    {
        return $this->transition($request, $reservation, Reservation::STATUS_CONFIRMED);
    }

    /** Rejects a pending reservation and releases its dates. */
    public function reject(Request $request, Reservation $reservation): ReservationResource
    {
        return $this->transition($request, $reservation, Reservation::STATUS_REJECTED);
    }

    /** Cancels an allowed staff-managed reservation state. */
    public function cancel(Request $request, Reservation $reservation): ReservationResource
    {
        return $this->transition($request, $reservation, Reservation::STATUS_CANCELLED);
    }

    /** Marks a confirmed rental as completed and releases its dates. */
    public function complete(Request $request, Reservation $reservation): ReservationResource
    {
        return $this->transition($request, $reservation, Reservation::STATUS_COMPLETED);
    }
}
