<?php

namespace App\Console\Commands;

use App\Events\CarAvailabilityChanged;
use App\Events\ReservationStatusChanged;
use App\Models\Reservation;
use App\Services\CarAvailabilityService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Safely reports or repairs historical overlapping holds in local/test environments. */
class RepairOverlappingReservations extends Command
{
    protected $signature = 'astra:repair-overlapping-reservations {--dry-run : Report proposed changes without writing data}';

    protected $description = 'Reject non-paid pending reservations that overlap an existing reservation hold';

    private const NOTE = 'Rejet automatique : période déjà réservée.';

    /** Runs a dry inspection or rejects repairable pending overlaps transactionally. */
    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Commande refusée : elle est réservée aux environnements local et testing.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $dryRun
            ? $this->inspect($this->blockingReservations())
            : DB::transaction(function (): array {
                $reservations = Reservation::query()
                    ->with('payments:id,reservation_id,status')
                    ->whereIn('status', CarAvailabilityService::BLOCKING_STATUSES)
                    ->orderBy('car_id')->orderBy('created_at')->orderBy('id')
                    ->lockForUpdate()->get();
                $result = $this->inspect($reservations);
                foreach ($result['repairable'] as $reservation) {
                    $reservation->update(['status' => 'rejected', 'internal_note' => self::NOTE]);
                }

                return $result;
            }, 3);

        $rows = collect($result['repairable'])->map(fn (Reservation $reservation) => [
            $reservation->id,
            $reservation->reservation_number,
            $reservation->car_id,
            $reservation->start_date->toDateString().' → '.$reservation->end_date->toDateString(),
            $dryRun ? 'REJET PROPOSÉ' : 'REJETÉ',
        ])->all();
        foreach ($result['protected'] as $reservation) {
            $rows[] = [$reservation->id, $reservation->reservation_number, $reservation->car_id, $reservation->start_date->toDateString().' → '.$reservation->end_date->toDateString(), 'IGNORÉ (PAYÉ)'];
        }
        foreach ($result['confirmed_conflicts'] as [$left, $right]) {
            $rows[] = [$right->id, $right->reservation_number, $right->car_id, $right->start_date->toDateString().' → '.$right->end_date->toDateString(), "CONFLIT CONFIRMÉ AVEC #{$left->id}"];
        }

        if ($rows === []) {
            $this->info('Aucun chevauchement bloquant trouvé.');
        } else {
            $this->table(['ID', 'Référence', 'Voiture', 'Période', 'Action'], $rows);
        }
        $this->line(sprintf(
            '%s : %d rejet(s), %d réservation(s) payée(s) protégée(s), %d conflit(s) confirmé(s) à examiner.',
            $dryRun ? 'Simulation' : 'Réparation',
            count($result['repairable']),
            count($result['protected']),
            count($result['confirmed_conflicts']),
        ));

        if (! $dryRun) {
            foreach ($result['repairable'] as $reservation) {
                $fresh = $reservation->fresh();
                rescue(fn () => event(new ReservationStatusChanged($fresh)), report: true);
                // Every repaired pending record overlaps a preserved blocker, so
                // clients must refresh without being told the dates were released.
                rescue(fn () => event(new CarAvailabilityChanged($fresh, 'unavailable')), report: true);
            }
        }

        return self::SUCCESS;
    }

    /** Loads blocking reservations in deterministic order for inspection. */
    private function blockingReservations(): Collection
    {
        return Reservation::query()
            ->with('payments:id,reservation_id,status')
            ->whereIn('status', CarAvailabilityService::BLOCKING_STATUSES)
            ->orderBy('car_id')->orderBy('created_at')->orderBy('id')->get();
    }

    /** Classifies overlaps without changing data. @return array{repairable: array, protected: array, confirmed_conflicts: array} */
    private function inspect(Collection $reservations): array
    {
        $repairable = [];
        $protected = [];
        $confirmedConflicts = [];

        foreach ($reservations->groupBy('car_id') as $forCar) {
            $confirmed = $forCar->where('status', 'confirmed')->values();
            for ($left = 0; $left < $confirmed->count(); $left++) {
                for ($right = $left + 1; $right < $confirmed->count(); $right++) {
                    if ($this->overlaps($confirmed[$left], $confirmed[$right])) {
                        $confirmedConflicts[] = [$confirmed[$left], $confirmed[$right]];
                    }
                }
            }

            $kept = $confirmed->all();
            foreach ($forCar->where('status', 'pending')->sortBy([['created_at', 'asc'], ['id', 'asc']]) as $pending) {
                if (! collect($kept)->contains(fn (Reservation $existing) => $this->overlaps($existing, $pending))) {
                    $kept[] = $pending;

                    continue;
                }

                if ($pending->payments->contains('status', 'paid')) {
                    $protected[] = $pending;
                    $kept[] = $pending;
                } else {
                    $repairable[] = $pending;
                }
            }
        }

        return [
            'repairable' => $repairable,
            'protected' => $protected,
            'confirmed_conflicts' => $confirmedConflicts,
        ];
    }

    /** Applies ASTRA's half-open overlap formula to two stored reservations. */
    private function overlaps(Reservation $existing, Reservation $requested): bool
    {
        return $existing->start_date->lt($requested->end_date)
            && $existing->end_date->gt($requested->start_date);
    }
}
