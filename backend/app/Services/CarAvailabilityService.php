<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for ASTRA rental dates and half-open availability.
 * Dates are parsed as calendar values in Africa/Casablanca; no UTC conversion
 * is performed, so a selected browser day cannot shift during validation.
 */
class CarAvailabilityService
{
    public const BLOCKING_STATUSES = [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED];

    /** Returns today's calendar date in ASTRA's configured Casablanca timezone. */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }

    /** Validates a half-open rental range. @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function normalizeRange(string|CarbonImmutable $startDate, string|CarbonImmutable $endDate): array
    {
        $start = $this->parseCalendarDate($startDate, 'start_date');
        $end = $this->parseCalendarDate($endDate, 'end_date');

        if ($start->lt($this->today())) {
            throw ValidationException::withMessages(['start_date' => 'La date de début ne peut pas être antérieure à aujourd’hui.']);
        }
        if ($end->lte($start)) {
            throw ValidationException::withMessages(['end_date' => 'La date de fin doit être postérieure à la date de début.']);
        }

        return [$start, $end];
    }

    /** Returns the billable days between an inclusive start and exclusive end. */
    public function rentalDays(string|CarbonImmutable $startDate, string|CarbonImmutable $endDate): int
    {
        [$start, $end] = $this->normalizeRange($startDate, $endDate);

        return (int) $start->diffInDays($end);
    }

    /** Finds pending or confirmed reservations that overlap the requested range. */
    public function findConflicts(Car $car, CarbonImmutable $startDate, CarbonImmutable $endDate, ?int $ignoreReservation = null, bool $lock = false): Collection
    {
        return Reservation::query()
            ->where('car_id', $car->id)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->when($ignoreReservation, fn (Builder $query) => $query->whereKeyNot($ignoreReservation))
            ->whereDate('start_date', '<', $endDate->toDateString())
            ->whereDate('end_date', '>', $startDate->toDateString())
            ->orderBy('start_date')
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->get();
    }

    /** Checks car status and reservation conflicts, optionally using row locks. */
    public function isAvailable(Car $car, CarbonImmutable $startDate, CarbonImmutable $endDate, ?int $ignoreReservation = null, bool $lock = false): bool
    {
        return $car->is_active
            && $car->operational_status === 'available'
            && $this->findConflicts($car, $startDate, $endDate, $ignoreReservation, $lock)->isEmpty();
    }

    /** Applies the shared overlap rule to a catalogue query. */
    public function applyAvailableForRange(Builder $cars, CarbonImmutable $startDate, CarbonImmutable $endDate): Builder
    {
        return $cars->whereDoesntHave('reservations', fn (Builder $query) => $query
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereDate('start_date', '<', $endDate->toDateString())
            ->whereDate('end_date', '>', $startDate->toDateString()));
    }

    /** Returns calendar-safe fields for each pending or confirmed hold. */
    public function getUnavailablePeriods(Car $car, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $periods = Reservation::query()
            ->where('car_id', $car->id)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereDate('start_date', '<', $to->toDateString())
            ->whereDate('end_date', '>', $from->toDateString())
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'status', 'updated_at'])
            ->map(fn (Reservation $reservation) => [
                'start_date' => $reservation->start_date->toDateString(),
                'end_date' => $reservation->end_date->toDateString(),
                'status' => $reservation->status,
                'updated_at' => $reservation->updated_at->toIso8601String(),
            ]);

        return $periods->values()->all();
    }

    /** Builds an authoritative price and availability quote from the server price. */
    public function quote(Car $car, string|CarbonImmutable $startDate, string|CarbonImmutable $endDate, bool $lock = false): array
    {
        [$start, $end] = $this->normalizeRange($startDate, $endDate);
        $days = (int) $start->diffInDays($end);

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'rental_days' => $days,
            'daily_price' => (float) $car->daily_price,
            'total_amount' => round($days * (float) $car->daily_price, 2),
            'currency' => 'MAD',
            'available' => $this->isAvailable($car, $start, $end, null, $lock),
        ];
    }

    /** Parses an exact YYYY-MM-DD value without allowing timezone date shifts. */
    private function parseCalendarDate(string|CarbonImmutable $value, string $field): CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->setTimezone(config('app.timezone'))->startOfDay();
        }
        if ($value === '') {
            $message = $field === 'start_date' ? 'Veuillez sélectionner une date de début.' : 'Veuillez sélectionner une date de fin.';
            throw ValidationException::withMessages([$field => $message]);
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
        $errors = CarbonImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages([$field => 'Veuillez utiliser une date valide au format AAAA-MM-JJ.']);
        }

        return $date;
    }
}
