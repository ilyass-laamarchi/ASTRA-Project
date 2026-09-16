<?php

namespace App\Services;

use App\Events\CarAvailabilityChanged;
use App\Events\ReservationStatusChanged;
use App\Models\Car;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

/** Applies the reservation state machine with MySQL row locks as final protection. */
class ReservationStatusService
{
    /** Receives the services required for conflict checks and client notifications. */
    public function __construct(private CarAvailabilityService $availability, private NotificationService $notifications) {}

    /** Applies an allowed state transition under row locks and broadcasts the result. */
    public function transition(Reservation $reservation, string $target, ?string $note = null): Reservation
    {
        [$updated, $previousStatus] = DB::transaction(function () use ($reservation, $target, $note): array {
            // Reload inside the transaction: stale tabs must not act on an old status.
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $allowed = ['pending' => ['confirmed', 'rejected', 'cancelled'], 'confirmed' => ['cancelled', 'completed']];
            abort_unless(in_array($target, $allowed[$locked->status] ?? [], true), 422, 'Transition de statut non autorisée.');

            if ($target === 'confirmed') {
                // Lock the car before the final overlap check to prevent simultaneous confirmations.
                $car = Car::query()->lockForUpdate()->findOrFail($locked->car_id);
                [$start, $end] = $this->availability->normalizeRange($locked->start_date->toDateString(), $locked->end_date->toDateString());
                abort_unless(
                    $this->availability->isAvailable($car, $start, $end, $locked->id),
                    409,
                    'Cette voiture vient d’être réservée pour tout ou partie de cette période. Veuillez choisir d’autres dates.'
                );
            }

            $previousStatus = $locked->status;
            $locked->update(['status' => $target, 'internal_note' => $note ?? $locked->internal_note]);

            return [$locked->fresh(['car.images', 'user', 'payments']), $previousStatus];
        }, 3);

        rescue(fn () => event(new ReservationStatusChanged($updated)), report: true);
        if (in_array($previousStatus, CarAvailabilityService::BLOCKING_STATUSES, true)
            && in_array($target, ['rejected', 'cancelled', 'completed'], true)) {
            rescue(fn () => event(new CarAvailabilityChanged($updated, 'available')), report: true);
        }

        $labels = ['confirmed' => 'confirmée', 'rejected' => 'refusée', 'cancelled' => 'annulée', 'completed' => 'terminée'];
        $this->notifications->send(
            $updated->user,
            'reservation.'.$target,
            'Réservation '.($labels[$target] ?? $target),
            'Votre réservation '.$updated->reservation_number.' a été '.($labels[$target] ?? $target).'.',
            ['reservation_id' => $updated->id, 'status' => $target]
        );

        return $updated;
    }
}
