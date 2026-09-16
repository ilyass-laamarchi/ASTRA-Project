<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\Events\CarAvailabilityChanged;
use App\Events\ReservationStatusChanged;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\CarAvailabilityService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Regression coverage for ASTRA's strict MySQL-backed reservation holds. */
class AvailabilitySystemTest extends TestCase
{
    use RefreshDatabase;

    /** Return a Casablanca date a fixed number of days ahead. */
    private function future(int $days): string
    {
        return CarbonImmutable::now('Africa/Casablanca')->addDays($days)->toDateString();
    }

    /** Create a reservation fixture with explicit dates and status. */
    private function reservation(Car $car, string $status, int $start = 10, int $end = 15, ?User $user = null): Reservation
    {
        return Reservation::factory()->create([
            'car_id' => $car->id,
            'user_id' => $user?->id ?? User::factory(),
            'start_date' => $this->future($start),
            'end_date' => $this->future($end),
            'status' => $status,
        ]);
    }

    /** Authenticate a fresh active client for the next request. */
    private function actAsClient(): User
    {
        $user = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($user);

        return $user;
    }

    /** Executes the regression scenario described by the method name. */
    public function test_invalid_and_reversed_dates_are_rejected(): void
    {
        $car = Car::factory()->create();
        $this->getJson("/api/cars/{$car->id}/availability?start_date={$this->future(-1)}&end_date={$this->future(2)}")
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->getJson("/api/cars/{$car->id}/availability?start_date={$this->future(4)}&end_date={$this->future(3)}")
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    /** Executes the regression scenario described by the method name. */
    public function test_server_calculates_five_rental_days_and_price(): void
    {
        $car = Car::factory()->create(['daily_price' => 420]);
        $quote = app(CarAvailabilityService::class)->quote($car, $this->future(10), $this->future(15));
        $this->assertSame(5, $quote['rental_days']);
        $this->assertSame(2100.0, $quote['total_amount']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_pending_and_confirmed_reservations_block_overlaps(): void
    {
        $service = app(CarAvailabilityService::class);
        foreach (CarAvailabilityService::BLOCKING_STATUSES as $status) {
            $car = Car::factory()->create();
            $this->reservation($car, $status);
            [$start, $end] = $service->normalizeRange($this->future(11), $this->future(13));
            $this->assertFalse($service->isAvailable($car, $start, $end), "{$status} must block");
        }
    }

    /** Executes the regression scenario described by the method name. */
    public function test_rejected_cancelled_and_completed_reservations_release_dates(): void
    {
        $service = app(CarAvailabilityService::class);
        foreach (['rejected', 'cancelled', 'completed'] as $status) {
            $car = Car::factory()->create();
            $this->reservation($car, $status);
            [$start, $end] = $service->normalizeRange($this->future(11), $this->future(13));
            $this->assertTrue($service->isAvailable($car, $start, $end), "{$status} must not block");
        }
    }

    /** Executes the regression scenario described by the method name. */
    public function test_exact_and_partial_overlap_return_required_http_409(): void
    {
        $car = Car::factory()->create();
        $this->reservation($car, 'pending');
        $this->actAsClient();
        $message = 'Cette voiture est déjà réservée ou temporairement bloquée pour tout ou partie de cette période. Veuillez choisir d’autres dates.';

        $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => $this->future(10), 'end_date' => $this->future(15)])
            ->assertConflict()->assertJsonPath('message', $message);
        $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => $this->future(14), 'end_date' => $this->future(18)])
            ->assertConflict()->assertJsonPath('message', $message);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_boundary_dates_and_same_dates_on_different_cars_are_allowed(): void
    {
        $firstCar = Car::factory()->create();
        $secondCar = Car::factory()->create();
        $this->reservation($firstCar, 'confirmed');
        $this->actAsClient();

        $this->postJson('/api/my-reservations', ['car_id' => $firstCar->id, 'start_date' => $this->future(15), 'end_date' => $this->future(18)])->assertCreated();
        $this->postJson('/api/my-reservations', ['car_id' => $secondCar->id, 'start_date' => $this->future(10), 'end_date' => $this->future(15)])->assertCreated();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_second_create_for_same_hold_is_rejected_and_only_one_pending_exists(): void
    {
        Event::fake();
        $car = Car::factory()->create();
        $this->actAsClient();
        $payload = ['car_id' => $car->id, 'start_date' => $this->future(20), 'end_date' => $this->future(24)];

        $this->postJson('/api/my-reservations', $payload)->assertCreated();
        $this->postJson('/api/my-reservations', $payload)->assertConflict();
        $this->assertSame(1, Reservation::where('car_id', $car->id)->where('status', 'pending')->count());
        Event::assertDispatched(CarAvailabilityChanged::class, 1);
        Event::assertDispatched(ReservationStatusChanged::class, 1);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_inactive_or_non_available_car_cannot_be_reserved(): void
    {
        $this->actAsClient();
        foreach ([['is_active' => false], ['operational_status' => 'maintenance'], ['operational_status' => 'unavailable']] as $state) {
            $car = Car::factory()->create($state);
            $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => $this->future(5), 'end_date' => $this->future(7)])->assertConflict();
        }
    }

    /** Executes the regression scenario described by the method name. */
    public function test_cancelling_pending_releases_dates_and_broadcasts(): void
    {
        Event::fake();
        $user = $this->actAsClient();
        $car = Car::factory()->create();
        $reservation = $this->reservation($car, 'pending', user: $user);

        $this->patchJson("/api/my-reservations/{$reservation->id}/cancel")->assertOk();
        $this->getJson("/api/cars/{$car->id}/availability?start_date={$this->future(10)}&end_date={$this->future(15)}")
            ->assertOk()->assertJsonPath('available', true);
        Event::assertDispatched(CarAvailabilityChanged::class, fn ($event) => $event->broadcastWith()['availability'] === 'available');
    }

    /** Executes the regression scenario described by the method name. */
    public function test_rejecting_pending_releases_dates_and_broadcasts(): void
    {
        Event::fake();
        $car = Car::factory()->create();
        $reservation = $this->reservation($car, 'pending');
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));

        $this->patchJson("/api/owner/reservations/{$reservation->id}/reject", ['internal_note' => 'Dates indisponibles.'])->assertOk();
        $this->getJson("/api/cars/{$car->id}/availability?start_date={$this->future(10)}&end_date={$this->future(15)}")
            ->assertOk()->assertJsonPath('available', true);
        Event::assertDispatched(CarAvailabilityChanged::class);
        Event::assertDispatched(ReservationStatusChanged::class);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_confirmation_ignores_current_pending_hold_and_keeps_dates_blocked(): void
    {
        Event::fake();
        $car = Car::factory()->create();
        $reservation = $this->reservation($car, 'pending');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->patchJson("/api/admin/reservations/{$reservation->id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'confirmed']);
        Event::assertDispatched(ReservationStatusChanged::class);
        Event::assertNotDispatched(CarAvailabilityChanged::class);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_confirmation_rejects_an_unexpected_other_blocking_reservation(): void
    {
        $car = Car::factory()->create();
        $pending = $this->reservation($car, 'pending');
        $this->reservation($car, 'confirmed');
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $this->patchJson("/api/owner/reservations/{$pending->id}/confirm")->assertConflict();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_unavailable_periods_include_pending_and_confirmed_with_safe_fields_only(): void
    {
        $car = Car::factory()->create();
        $this->reservation($car, 'pending', 10, 12)->update(['internal_note' => 'secret pending']);
        $this->reservation($car, 'confirmed', 14, 18)->update(['internal_note' => 'secret confirmed']);

        $response = $this->getJson("/api/cars/{$car->id}/unavailable-periods?from={$this->future(1)}&to={$this->future(30)}")
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.1.status', 'confirmed')
            ->assertJsonStructure(['data' => [['start_date', 'end_date', 'status', 'updated_at']]]);
        foreach (['secret', 'email', 'reservation_number', 'total_amount'] as $privateField) {
            $this->assertStringNotContainsString($privateField, $response->getContent());
        }
    }

    /** Executes the regression scenario described by the method name. */
    public function test_catalogue_excludes_pending_and_confirmed_holds_for_requested_dates(): void
    {
        $pendingCar = Car::factory()->create();
        $confirmedCar = Car::factory()->create();
        $freeCar = Car::factory()->create();
        $this->reservation($pendingCar, 'pending');
        $this->reservation($confirmedCar, 'confirmed');

        $ids = collect($this->getJson("/api/cars?start_date={$this->future(11)}&end_date={$this->future(13)}")->assertOk()->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($pendingCar->id));
        $this->assertFalse($ids->contains($confirmedCar->id));
        $this->assertTrue($ids->contains($freeCar->id));
    }

    /** Executes the regression scenario described by the method name. */
    public function test_checkout_configuration_and_confirmed_unpaid_checkout_rules(): void
    {
        $this->app->instance(PaymentGatewayInterface::class, new class implements PaymentGatewayInterface
        {
            /** Return a deterministic fake checkout response. */
            public function createCheckout(Payment $payment): array
            {
                return ['session_id' => 'cs_test_example', 'checkout_url' => 'https://checkout.stripe.test/session'];
            }

            /** Return a deterministic fake webhook event. */
            public function parseWebhook(string $payload, string $signature): array
            {
                return [];
            }

            /** Return a deterministic fake refund response. */
            public function refund(Payment $payment): array
            {
                return [];
            }
        });
        $user = $this->actAsClient();
        $car = Car::factory()->create();
        $confirmed = $this->reservation($car, 'confirmed', user: $user);
        $pending = $this->reservation(Car::factory()->create(), 'pending', user: $user);

        $this->getJson('/api/payment-configuration')->assertOk()->assertJsonPath('checkout_available', false);
        $this->postJson("/api/reservations/{$pending->id}/checkout")->assertUnprocessable();
        $this->postJson("/api/reservations/{$confirmed->id}/checkout")->assertStatus(503)->assertJsonPath('message', 'Paiement temporairement indisponible');

        config(['services.payment.public' => 'pk_test_example', 'services.payment.secret' => 'sk_test_example', 'services.payment.webhook_secret' => 'whsec_example']);

        $this->getJson('/api/payment-configuration')->assertJsonPath('checkout_available', true);
        $this->postJson("/api/reservations/{$confirmed->id}/checkout")->assertOk()->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session');
        Payment::where('reservation_id', $confirmed->id)->update(['status' => 'paid']);
        $this->postJson("/api/reservations/{$confirmed->id}/checkout")->assertConflict();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_client_cannot_pay_another_clients_reservation(): void
    {
        $this->actAsClient();
        $reservation = $this->reservation(Car::factory()->create(), 'confirmed');
        $this->postJson("/api/reservations/{$reservation->id}/checkout")->assertForbidden();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_database_seeder_creates_no_blocking_overlaps(): void
    {
        $this->seed(DatabaseSeeder::class);
        $count = DB::table('reservations as left_reservation')
            ->join('reservations as right_reservation', function ($join): void {
                $join->on('left_reservation.car_id', '=', 'right_reservation.car_id')
                    ->whereColumn('left_reservation.id', '<', 'right_reservation.id')
                    ->whereColumn('left_reservation.start_date', '<', 'right_reservation.end_date')
                    ->whereColumn('left_reservation.end_date', '>', 'right_reservation.start_date');
            })
            ->whereIn('left_reservation.status', CarAvailabilityService::BLOCKING_STATUSES)
            ->whereIn('right_reservation.status', CarAvailabilityService::BLOCKING_STATUSES)
            ->count();
        $this->assertSame(0, $count);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_repair_command_dry_run_reports_without_modifying_data(): void
    {
        $car = Car::factory()->create();
        $this->reservation($car, 'confirmed');
        $pending = $this->reservation($car, 'pending');
        $beforeUpdatedAt = $pending->updated_at->toIso8601String();

        $this->artisan('astra:repair-overlapping-reservations', ['--dry-run' => true])
            ->expectsOutputToContain('Simulation : 1 rejet(s)')->assertSuccessful();
        $fresh = $pending->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->internal_note);
        $this->assertSame($beforeUpdatedAt, $fresh->updated_at->toIso8601String());
    }

    /** Executes the regression scenario described by the method name. */
    public function test_casablanca_calendar_date_never_shifts(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-04 00:30:00', 'Africa/Casablanca'));
        [$start] = app(CarAvailabilityService::class)->normalizeRange('2026-08-04', '2026-08-05');
        $this->assertSame('2026-08-04', $start->toDateString());
        CarbonImmutable::setTestNow();
    }
}
