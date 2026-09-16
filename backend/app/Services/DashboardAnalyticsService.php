<?php

namespace App\Services;

use App\Models\AstraNotification;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/** Builds authoritative, role-specific dashboard analytics from ASTRA business data. */
class DashboardAnalyticsService
{
    public const PERIODS = ['today', '7d', '30d', 'month', 'year'];

    /** Builds the operational or strategic dashboard allowed for the user's role. */
    public function build(User $user, string $period): array
    {
        [$start, $end, $buckets] = $this->range($period);
        $today = Carbon::today();
        $cars = Car::with('category')->orderBy('brand')->orderBy('model')->get();
        $periodReservations = Reservation::with(['user:id,first_name,last_name,email', 'car:id,brand,model,registration_number', 'payments:id,reservation_id,status'])
            ->whereBetween('created_at', [$start, $end])->get();

        $activeCarIds = Reservation::where('status', 'confirmed')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>', $today)
            ->pluck('car_id')->unique();

        $fleet = [
            'available' => $cars->where('is_active', true)->where('operational_status', 'available')->whereNotIn('id', $activeCarIds)->count(),
            'rented_reserved' => $cars->whereIn('id', $activeCarIds)->count(),
            'maintenance' => $cars->where('operational_status', 'maintenance')->count(),
            'unavailable' => $cars->filter(fn (Car $car) => ! $car->is_active || $car->operational_status === 'unavailable')->count(),
        ];
        $activeFleet = max(1, $cars->where('is_active', true)->count());
        $occupancy = round(($fleet['rented_reserved'] / $activeFleet) * 100, 1);

        $common = [
            'role' => $user->role,
            'period' => $period,
            'generated_at' => now()->toIso8601String(),
            'fleet_distribution' => $fleet,
            'category_distribution' => $cars->groupBy(fn (Car $car) => $car->category?->name ?? 'Sans catégorie')
                ->map(fn (Collection $items, string $name) => ['label' => $name, 'value' => $items->count()])->values(),
            'reservation_activity' => $this->reservationSeries($periodReservations, $buckets),
            'latest_reservations' => $this->serializeReservations(
                Reservation::with(['user:id,first_name,last_name,email', 'car:id,brand,model,registration_number', 'payments:id,reservation_id,status'])
                    ->latest()->limit(8)->get()
            ),
        ];

        return $user->role === 'admin'
            ? array_merge($common, $this->admin($user, $start, $end, $buckets, $periodReservations, $cars, $occupancy))
            : array_merge($common, $this->operations($today, $periodReservations, $cars, $fleet, $occupancy));
    }

    /** Builds daily operational KPIs and alerts for the Responsable dashboard. */
    private function operations(Carbon $today, Collection $reservations, Collection $cars, array $fleet, float $occupancy): array
    {
        $planning = Reservation::with(['user:id,first_name,last_name', 'car:id,brand,model,registration_number'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($today): void {
                $query->whereBetween('start_date', [$today, $today->copy()->addDays(7)])
                    ->orWhereBetween('end_date', [$today, $today->copy()->addDays(7)]);
            })->orderBy('start_date')->limit(10)->get()->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'reference' => $reservation->reservation_number,
                'client' => trim(($reservation->user?->first_name ?? '').' '.($reservation->user?->last_name ?? '')),
                'vehicle' => trim(($reservation->car?->brand ?? '').' '.($reservation->car?->model ?? '')),
                'departure' => $reservation->start_date?->toDateString(),
                'return' => $reservation->end_date?->toDateString(),
                'status' => $reservation->status,
            ])->values();

        $pending = Reservation::where('status', 'pending')->count();
        $returnsToday = Reservation::where('status', 'confirmed')->whereDate('end_date', $today)->count();
        $unpaidConfirmed = Reservation::where('status', 'confirmed')
            ->whereDoesntHave('payments', fn ($query) => $query->where('status', 'paid'))->count();

        return [
            'dashboard_type' => 'operations',
            'kpis' => [
                'reservations_today' => Reservation::whereDate('created_at', $today)->count(),
                'pending_reservations' => $pending,
                'confirmed_reservations' => $reservations->where('status', 'confirmed')->count(),
                'returns_today' => $returnsToday,
                'available_vehicles' => $fleet['available'],
                'rented_vehicles' => $fleet['rented_reserved'],
                'maintenance_vehicles' => $fleet['maintenance'],
                'occupancy_rate' => $occupancy,
            ],
            'planning' => $planning,
            'alerts' => collect([
                ['type' => 'pending', 'label' => 'Réservations à confirmer', 'count' => $pending],
                ['type' => 'maintenance', 'label' => 'Véhicules nécessitant une attention', 'count' => $cars->whereIn('operational_status', ['maintenance', 'unavailable'])->count()],
                ['type' => 'return', 'label' => 'Retours prévus aujourd’hui', 'count' => $returnsToday],
                ['type' => 'payment', 'label' => 'Réservations confirmées non payées', 'count' => $unpaidConfirmed],
            ])->filter(fn (array $alert) => $alert['count'] > 0)->values(),
        ];
    }

    /** Builds administrator-only revenue, client, and fleet analytics. */
    private function admin(User $user, Carbon $start, Carbon $end, array $buckets, Collection $reservations, Collection $cars, float $occupancy): array
    {
        $paidPayments = Payment::with('reservation:id,car_id')->where('status', 'paid')->whereBetween('paid_at', [$start, $end])->get();
        $revenue = (float) $paidPayments->sum('amount');
        $paidReservations = max(1, $paidPayments->pluck('reservation_id')->unique()->count());
        // Reuse loaded period data to avoid database queries inside the car loop.
        $performance = $cars->map(function (Car $car) use ($start, $end, $reservations, $paidPayments): array {
            $carReservations = $reservations->where('car_id', $car->id);
            $valid = $carReservations->whereIn('status', ['confirmed', 'completed']);
            $revenue = (float) $paidPayments->whereIn('reservation_id', $carReservations->pluck('id'))->sum('amount');
            $days = (int) $valid->sum('rental_days');
            $rangeDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1);

            return [
                'id' => $car->id,
                'vehicle' => $car->brand.' '.$car->model,
                'reservations' => $carReservations->count(),
                'booked_days' => $days,
                'revenue' => round($revenue, 2),
                'utilization' => min(100, round(($days / $rangeDays) * 100, 1)),
                'status' => $car->operational_status,
            ];
        })->sortByDesc('revenue')->values();

        $statusOrder = ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'];
        $statusDistribution = collect($statusOrder)->map(fn (string $status) => [
            'label' => $status,
            'value' => $reservations->where('status', $status)->count(),
        ]);

        return [
            'dashboard_type' => 'admin',
            'kpis' => [
                'revenue' => round($revenue, 2),
                'reservations' => $reservations->count(),
                'active_clients' => User::where('role', 'client')->where('is_active', true)->count(),
                'active_vehicles' => $cars->where('is_active', true)->count(),
                'payments_received' => $paidPayments->count(),
                'payments_pending' => Payment::whereIn('status', ['pending', 'processing'])->whereBetween('created_at', [$start, $end])->count(),
                'occupancy_rate' => $occupancy,
                'average_booking_revenue' => round($revenue / $paidReservations, 2),
            ],
            'revenue_series' => $this->revenueSeries($paidPayments, $buckets),
            'revenue_by_vehicle' => $performance->take(8)->map(fn (array $row) => ['label' => $row['vehicle'], 'value' => $row['revenue']])->values(),
            'vehicle_performance' => $performance->take(10),
            'reservation_status' => $statusDistribution,
            'clients' => [
                'total' => User::where('role', 'client')->count(),
                'new_this_month' => User::where('role', 'client')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'returning' => User::where('role', 'client')->whereHas('reservations', fn ($query) => $query->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1'))->count(),
                'with_active_reservations' => User::where('role', 'client')->whereHas('reservations', fn ($query) => $query->whereIn('status', ['pending', 'confirmed']))->count(),
            ],
            'recent_activity' => AstraNotification::where('user_id', $user->id)->latest()->limit(8)->get()
                ->map(fn (AstraNotification $notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])->values(),
        ];
    }

    /** Groups reservation counts into the selected chart buckets. */
    private function reservationSeries(Collection $reservations, array $buckets): array
    {
        return collect($buckets)->map(function (array $bucket) use ($reservations): array {
            $items = $reservations->filter(fn (Reservation $reservation) => $reservation->created_at->betweenIncluded($bucket['start'], $bucket['end']));

            return [
                'label' => $bucket['label'],
                'pending' => $items->where('status', 'pending')->count(),
                'confirmed' => $items->where('status', 'confirmed')->count(),
                'completed' => $items->where('status', 'completed')->count(),
                'cancelled_rejected' => $items->whereIn('status', ['cancelled', 'rejected'])->count(),
            ];
        })->values()->all();
    }

    /** Groups verified paid revenue into the selected chart buckets. */
    private function revenueSeries(Collection $payments, array $buckets): array
    {
        return collect($buckets)->map(fn (array $bucket) => [
            'label' => $bucket['label'],
            'value' => round((float) $payments->filter(fn (Payment $payment) => $payment->paid_at?->betweenIncluded($bucket['start'], $bucket['end']))->sum('amount'), 2),
        ])->values()->all();
    }

    /** Converts reservations to the compact shape used by dashboard tables. */
    private function serializeReservations(Collection $reservations): Collection
    {
        return $reservations->map(fn (Reservation $reservation) => [
            'id' => $reservation->id,
            'reference' => $reservation->reservation_number,
            'client' => trim(($reservation->user?->first_name ?? '').' '.($reservation->user?->last_name ?? '')),
            'vehicle' => trim(($reservation->car?->brand ?? '').' '.($reservation->car?->model ?? '')),
            'start_date' => $reservation->start_date?->toDateString(),
            'end_date' => $reservation->end_date?->toDateString(),
            'amount' => (float) $reservation->total_amount,
            'status' => $reservation->status,
            'payment_status' => $reservation->payments->contains('status', 'paid') ? 'paid' : ($reservation->payments->whereIn('status', ['pending', 'processing'])->isNotEmpty() ? 'pending' : 'unpaid'),
        ])->values();
    }

    /** Resolves a named period into start/end dates and chart buckets. */
    private function range(string $period): array
    {
        $now = now();
        [$start, $end] = match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        if ($period === 'year') {
            $buckets = collect(range(1, 12))->map(function (int $month) use ($now): array {
                $date = Carbon::create($now->year, $month, 1);

                return ['label' => $date->translatedFormat('M'), 'start' => $date->copy()->startOfMonth(), 'end' => $date->copy()->endOfMonth()];
            })->all();
        } else {
            $buckets = collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
                ->map(fn (Carbon $date) => ['label' => $date->format('d/m'), 'start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay()])->all();
        }

        return [$start, $end, $buckets];
    }
}
