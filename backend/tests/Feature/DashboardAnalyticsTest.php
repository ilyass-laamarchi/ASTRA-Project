<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /** Executes the regression scenario described by the method name. */
    public function test_admin_receives_strategic_analytics_and_revenue_uses_paid_payments_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reservation = Reservation::factory()->create(['status' => 'confirmed', 'created_at' => now()]);
        Payment::create([
            'reservation_id' => $reservation->id, 'user_id' => $reservation->user_id,
            'payment_reference' => 'PAY-PAID-1', 'amount' => 1200, 'currency' => 'MAD',
            'status' => 'paid', 'paid_at' => now(),
        ]);
        Payment::create([
            'reservation_id' => $reservation->id, 'user_id' => $reservation->user_id,
            'payment_reference' => 'PAY-PENDING-1', 'amount' => 9000, 'currency' => 'MAD',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/admin/dashboard?period=30d')->assertOk()
            ->assertJsonPath('data.dashboard_type', 'admin')
            ->assertJsonPath('data.kpis.revenue', 1200)
            ->assertJsonStructure(['data' => ['revenue_series', 'revenue_by_vehicle', 'vehicle_performance', 'clients', 'recent_activity']]);

        $this->assertArrayNotHasKey('alerts', $response->json('data'));
    }

    /** Executes the regression scenario described by the method name. */
    public function test_responsable_receives_operations_without_admin_business_analytics(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $response = $this->getJson('/api/owner/dashboard?period=7d')->assertOk()
            ->assertJsonPath('data.dashboard_type', 'operations')
            ->assertJsonStructure(['data' => ['kpis', 'planning', 'alerts', 'fleet_distribution', 'reservation_activity']]);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('revenue_series', $data);
        $this->assertArrayNotHasKey('vehicle_performance', $data);
        $this->assertArrayNotHasKey('clients', $data);
    }

    /** Executes the regression scenario described by the method name. */
    public function test_client_cannot_access_staff_analytics_routes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'client']));
        $this->getJson('/api/admin/dashboard')->assertForbidden();
        $this->getJson('/api/owner/dashboard')->assertForbidden();
    }
}
