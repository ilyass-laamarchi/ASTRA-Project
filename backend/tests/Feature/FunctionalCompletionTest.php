<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\AstraNotification;
use App\Models\CarImage;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\DemoFleetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FunctionalCompletionTest extends TestCase
{
    use RefreshDatabase;

    /** Executes the regression scenario described by the method name. */
    public function test_profile_password_and_preferences_are_persisted(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        Sanctum::actingAs($user);
        $this->patchJson('/api/profile', ['first_name' => 'Nora', 'last_name' => 'Alaoui', 'email' => 'nora@example.test', 'phone' => '+212600000010'])->assertOk()->assertJsonPath('user.first_name', 'Nora');
        $this->putJson('/api/password', ['current_password' => 'Password123!', 'password' => 'Changed123!', 'password_confirmation' => 'Changed123!'])->assertOk();
        $this->putJson('/api/notification-preferences', ['reservations' => true, 'payments' => false, 'system' => true])->assertOk();
        $this->assertFalse($user->fresh()->notification_preferences['payments']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_password_reset_token_flow_changes_the_password(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $token = Password::createToken($user);
        $this->postJson('/api/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'ResetPass123!', 'password_confirmation' => 'ResetPass123!'])->assertOk();
        $this->assertTrue(Hash::check('ResetPass123!', $user->fresh()->password));
    }

    /** Executes the regression scenario described by the method name. */
    public function test_notifications_are_private_and_can_be_marked_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = AstraNotification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'Test', 'message' => 'Message']);
        Sanctum::actingAs($other);
        $this->patchJson('/api/notifications/'.$notification->id.'/read')->assertForbidden();
        Sanctum::actingAs($user);
        $this->getJson('/api/notifications')->assertOk()->assertJsonPath('data.data.0.title', 'Test');
        $this->patchJson('/api/notifications/'.$notification->id.'/read')->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_disabled_notification_preference_prevents_delivery(): void
    {
        $user = User::factory()->create(['notification_preferences' => ['reservations' => false, 'payments' => true, 'system' => true]]);
        $this->assertNull(app(NotificationService::class)->send($user, 'reservation.confirmed', 'Confirmation', 'Votre réservation est confirmée.'));
        $this->assertDatabaseMissing('astra_notifications', ['user_id' => $user->id, 'type' => 'reservation.confirmed']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_only_admin_can_persist_system_settings_and_create_staff(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Sanctum::actingAs($owner);
        $this->getJson('/api/admin/settings')->assertForbidden();
        $this->postJson('/api/admin/staff', [])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $payload = ['agency_name' => 'ASTRA Tanger', 'agency_email' => 'contact@astra.ma', 'agency_phone' => '+212539000000', 'agency_address' => 'Tanger, Maroc', 'currency' => 'MAD', 'timezone' => 'Africa/Casablanca', 'booking_notice_hours' => 24];
        $this->putJson('/api/admin/settings', $payload)->assertOk()->assertJsonPath('data.agency_name', 'ASTRA Tanger');
        $created = $this->postJson('/api/admin/staff', ['first_name' => 'Samira', 'last_name' => 'Idrissi', 'email' => 'samira@astra.test', 'phone' => null, 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'role' => 'admin', 'is_active' => true])->assertCreated()->assertJsonPath('data.role', 'owner');
        $this->putJson('/api/admin/staff/'.$created->json('data.id'), ['first_name' => 'Samira', 'last_name' => 'Alaoui', 'email' => 'samira@astra.test', 'phone' => '+212600000012', 'role' => 'admin', 'password' => '', 'password_confirmation' => ''])->assertOk()->assertJsonPath('data.role', 'owner');
        $this->assertSame('ASTRA Tanger', ApplicationSetting::find('agency_name')->value);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_contact_messages_are_stored_and_notify_staff(): void
    {
        User::factory()->create(['role' => 'admin']);
        $this->postJson('/api/contact', ['name' => 'Youssef', 'email' => 'youssef@example.test', 'phone' => '+212600000011', 'message' => 'Je souhaite une location longue durée.'])->assertCreated();
        $this->assertDatabaseHas('contact_inquiries', ['email' => 'youssef@example.test', 'status' => 'new']);
        $this->assertDatabaseHas('astra_notifications', ['type' => 'contact.created']);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_demo_fleet_seeder_is_idempotent_and_every_car_has_its_own_asset(): void
    {
        $this->seed(DemoFleetSeeder::class);
        $this->seed(DemoFleetSeeder::class);
        $this->assertDatabaseCount('cars', 14);
        $this->assertDatabaseCount('car_images', 14);
        $this->assertSame(14, CarImage::distinct('path')->count('path'));
    }
}
