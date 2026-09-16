<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/** Verifies the highest-risk auth, pricing, ownership, and date rules. */
class AstraApiTest extends TestCase
{
    use RefreshDatabase;

    /** Executes the regression scenario described by the method name. */
    public function test_authenticated_user_can_upload_only_their_own_persistent_avatar(): void
    {
        config(['filesystems.disks.public.url' => 'http://localhost:8000/storage']);
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create(['avatar_path' => null]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            ),
        ]);

        $response->assertOk()->assertJsonPath('user.id', $user->id);
        $avatarUrl = $response->json('user.avatar_path');
        $this->assertStringContainsString('/storage/avatars/', $avatarUrl);
        Storage::disk('public')->assertExists(substr(parse_url($avatarUrl, PHP_URL_PATH), strlen('/storage/')));
        $this->assertNull($other->fresh()->avatar_path);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_client_dashboard_sources_return_only_the_authenticated_clients_records(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $otherClient = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $ownReservation = Reservation::factory()->create(['user_id' => $client->id]);
        $otherReservation = Reservation::factory()->create(['user_id' => $otherClient->id]);
        Payment::create(['reservation_id' => $ownReservation->id, 'user_id' => $client->id, 'payment_reference' => 'PAY-OWN', 'amount' => 500, 'currency' => 'MAD', 'status' => 'pending']);
        Payment::create(['reservation_id' => $otherReservation->id, 'user_id' => $otherClient->id, 'payment_reference' => 'PAY-OTHER', 'amount' => 900, 'currency' => 'MAD', 'status' => 'paid']);
        Sanctum::actingAs($client);

        $reservationResponse = $this->getJson('/api/my-reservations')->assertOk();
        $paymentResponse = $this->getJson('/api/my-payments')->assertOk();

        $this->assertSame([$ownReservation->id], collect($reservationResponse->json('data'))->pluck('id')->all());
        $this->assertSame([$ownReservation->id], collect($paymentResponse->json('data.data'))->pluck('reservation_id')->all());
    }

    /** Executes the regression scenario described by the method name. */
    public function test_public_registration_is_always_a_client(): void
    {
        $response = $this->postJson('/api/register', ['first_name' => 'Aya', 'last_name' => 'Test', 'email' => 'aya@example.test', 'phone' => '0600000000', 'password' => 'Password123!', 'password_confirmation' => 'Password123!']);
        $response->assertCreated()->assertJsonPath('user.role', 'client');
        $this->assertDatabaseHas('users', ['email' => 'aya@example.test', 'role' => User::ROLE_CLIENT]);
    }

    /** New client accounts are visible first and remain accessible across pages and search. */
    public function test_admin_client_directory_lists_newest_clients_with_complete_pagination(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(16)->create(['role' => User::ROLE_CLIENT]);
        $newest = User::factory()->create([
            'first_name' => 'Nouvelle',
            'last_name' => 'Cliente',
            'email' => 'nouvelle.client@example.test',
            'role' => User::ROLE_CLIENT,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/clients')
            ->assertOk()
            ->assertJsonCount(15, 'data.data')
            ->assertJsonPath('data.data.0.id', $newest->id)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonPath('data.total', 17);

        $this->getJson('/api/admin/clients?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.data');

        $this->getJson('/api/admin/clients?search=nouvelle.client%40example.test')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $newest->id);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_public_registration_rejects_admin_role_injection(): void
    {
        $response = $this->postJson('/api/register', ['first_name' => 'Malicious', 'last_name' => 'Admin', 'email' => 'admin-injection@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'role' => 'admin', 'is_admin' => true, 'permissions' => ['*']]);
        $response->assertUnprocessable()->assertJsonValidationErrors(['role', 'is_admin', 'permissions']);
        $this->assertDatabaseMissing('users', ['email' => 'admin-injection@example.test']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_public_registration_rejects_owner_role_injection(): void
    {
        $response = $this->postJson('/api/register', ['first_name' => 'Malicious', 'last_name' => 'Owner', 'email' => 'owner-injection@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'role' => 'owner', 'is_staff' => true, 'user_type' => 'responsable']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['role', 'is_staff', 'user_type']);
        $this->assertDatabaseMissing('users', ['email' => 'owner-injection@example.test']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Password123!'])->assertForbidden();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_inactive_and_maintenance_cars_are_not_public(): void
    {
        Car::factory()->create(['is_active' => false]);
        Car::factory()->create(['operational_status' => 'maintenance']);
        Car::factory()->create();
        $this->getJson('/api/cars')->assertOk()->assertJsonCount(1, 'data');
    }

    /** Executes the regression scenario described by the method name. */
    public function test_server_calculates_price_and_ignores_client_amount(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create(['daily_price' => 500]);
        Sanctum::actingAs($user);
        $start = now()->addDays(5)->toDateString();
        $end = now()->addDays(10)->toDateString();
        $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => $start, 'end_date' => $end, 'daily_price' => 1, 'total_amount' => 1])->assertCreated()->assertJsonPath('data.rental_days', 5)->assertJsonPath('data.total_amount', 2500);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_reservation_creation_route_requires_authentication(): void
    {
        $car = Car::factory()->create();

        $this->postJson('/api/my-reservations', [
            'car_id' => $car->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ])->assertUnauthorized();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_created_reservation_appears_in_client_reservations(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create(['daily_price' => 330]);
        Sanctum::actingAs($user);
        $start = now()->addDays(20)->toDateString();
        $end = now()->addDays(22)->toDateString();

        $created = $this->postJson('/api/my-reservations', [
            'car_id' => $car->id,
            'start_date' => $start,
            'end_date' => $end,
        ])->assertCreated()->assertJsonPath('data.rental_days', 2)->assertJsonPath('data.total_amount', 660);

        $this->getJson('/api/my-reservations')
            ->assertOk()
            ->assertJsonFragment(['id' => $created->json('data.id'), 'status' => 'pending']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_confirmed_half_open_period_blocks_overlap_but_allows_boundary(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create();
        Reservation::factory()->create(['car_id' => $car->id, 'start_date' => now()->addDays(10), 'end_date' => now()->addDays(15), 'status' => 'confirmed']);
        Sanctum::actingAs($user);
        $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => now()->addDays(14)->toDateString(), 'end_date' => now()->addDays(16)->toDateString()])->assertConflict();
        $this->postJson('/api/my-reservations', ['car_id' => $car->id, 'start_date' => now()->addDays(15)->toDateString(), 'end_date' => now()->addDays(17)->toDateString()])->assertCreated();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_client_cannot_read_another_clients_reservation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($other);
        $this->getJson('/api/my-reservations/'.$reservation->id)->assertForbidden();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_pending_reservation_cannot_be_paid(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Sanctum::actingAs($user);
        $this->postJson('/api/reservations/'.$reservation->id.'/checkout')->assertUnprocessable();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_owner_cannot_create_staff_or_refund(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Sanctum::actingAs($owner);
        $this->postJson('/api/admin/staff', [])->assertForbidden();
        $this->postJson('/api/owner/payments/1/refund')->assertNotFound();
    }

    /** Executes the regression scenario described by the method name. */
    public function test_google_oauth_redirect_fails_closed_without_credentials(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $this->getJson('/api/auth/google/redirect')->assertStatus(503)->assertJsonPath('message', 'Connexion Google temporairement indisponible.');
    }

    /** Executes the regression scenario described by the method name. */
    public function test_google_oauth_callback_creates_a_client_and_returns_a_token(): void
    {
        Socialite::fake('google', SocialiteUser::fake(['name' => 'Nora Test', 'email' => 'nora.google@example.test', 'given_name' => 'Nora', 'family_name' => 'Test']));
        $response = $this->get('/api/auth/google/callback');
        $response->assertRedirect();
        $this->assertStringStartsWith('http://localhost:5173/auth/callback?token=', $response->headers->get('Location'));
        $this->assertDatabaseHas('users', ['email' => 'nora.google@example.test', 'role' => 'client', 'is_active' => true]);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_google_oauth_preserves_an_existing_admin_role(): void
    {
        $admin = User::factory()->create(['email' => 'existing.admin@example.test', 'role' => User::ROLE_ADMIN]);
        Socialite::fake('google', SocialiteUser::fake(['name' => 'Existing Admin', 'email' => $admin->email, 'given_name' => 'Existing', 'family_name' => 'Admin']));

        $this->get('/api/auth/google/callback')->assertRedirect();

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_client_is_denied_all_admin_management_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_CLIENT]));

        $this->getJson('/api/admin/staff')->assertForbidden();
        $this->getJson('/api/admin/settings')->assertForbidden();
        $this->getJson('/api/admin/cars')->assertForbidden();
    }
}
