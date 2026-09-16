<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Generates correctly calculated reservation snapshots for workflow tests. */
class ReservationFactory extends Factory
{
    /** Returns a valid future reservation with server-shaped money fields. */
    public function definition(): array
    {
        $start = now()->addDays(10)->startOfDay();
        $days = 5;
        $price = 500;

        return ['reservation_number' => 'AST-TEST-'.strtoupper(Str::random(6)), 'user_id' => User::factory(), 'car_id' => Car::factory(), 'start_date' => $start, 'end_date' => $start->copy()->addDays($days), 'rental_days' => $days, 'daily_price' => $price, 'total_amount' => $days * $price, 'status' => 'pending'];
    }
}
