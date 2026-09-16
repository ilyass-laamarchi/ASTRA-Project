<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Seeds a coherent local demonstration without overlapping confirmed rentals. */
class DatabaseSeeder extends Seeder
{
    /** Builds local demonstration accounts, fleet data, settings, and reservations. */
    public function run(): void
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'ASTRA', 'email' => 'admin@astra.ma', 'phone' => '+212 600 000 001', 'password' => Hash::make('Password123!'), 'role' => 'admin', 'is_active' => true]);
        $owner = User::create(['first_name' => 'Responsable', 'last_name' => 'ASTRA', 'email' => 'owner@astra.test', 'phone' => '+212 600 000 002', 'password' => Hash::make('Password123!'), 'role' => 'owner', 'is_active' => true]);
        $client = User::create(['first_name' => 'Amine', 'last_name' => 'Bennani', 'email' => 'client@astra.ma', 'phone' => '+212 600 000 003', 'password' => Hash::make('Password123!'), 'role' => 'client', 'is_active' => true]);

        $categories = collect([
            ['name' => 'Citadine', 'description' => 'Agile, économique et idéale pour la ville.'],
            ['name' => 'Berline', 'description' => 'Confort raffiné pour les trajets professionnels.'],
            ['name' => 'SUV', 'description' => 'Espace et polyvalence pour toutes les routes.'],
            ['name' => 'Prestige', 'description' => 'Une expérience premium pour les occasions particulières.'],
        ])->map(fn ($data) => Category::create($data + ['is_active' => true]));

        $vehicles = [
            ['Renault', 'Clio', 'Citadine', 2024, 'Bleu', 5, 5, 'gasoline', 'manual', 350, 18000, 'Une citadine fluide et sobre pour vos déplacements quotidiens.', '/assets/images/renault-clio-900.webp'],
            ['Peugeot', '308', 'Berline', 2025, 'Blanc', 5, 5, 'diesel', 'automatic', 590, 9000, 'Une berline précise, confortable et parfaitement équipée.', '/assets/images/peugeot-308-900.webp'],
            ['Dacia', 'Duster', 'SUV', 2024, 'Gris', 5, 5, 'diesel', 'manual', 520, 22000, 'Un SUV polyvalent avec un excellent volume de chargement.', '/assets/images/dacia-duster-900.webp'],
            ['Lamborghini', 'Urus', 'Prestige', 2024, 'Jaune', 5, 5, 'gasoline', 'automatic', 4500, 6500, 'Super SUV à moteur V8 biturbo de 4,0 litres développant 650 ch, avec boîte automatique à 8 rapports et transmission intégrale permanente.', '/assets/images/hero-car-cutout.png'],
            ['Tesla', 'Model 3', 'Prestige', 2024, 'Blanc nacré', 5, 4, 'electric', 'automatic', 1150, 12000, 'Une conduite électrique dynamique dans un habitacle épuré.', '/assets/images/tesla-model-3-900.webp'],
            ['Mercedes', 'Classe C', 'Prestige', 2023, 'Graphite', 5, 4, 'diesel', 'automatic', 1290, 31000, 'Une berline de représentation élégante et sereine.', '/assets/images/mercedes-classe-c-900.webp'],
        ];

        foreach ($vehicles as $index => $v) {
            [$brand, $model, $category, $year, $color, $seats, $doors, $fuel, $transmission, $price, $mileage, $description, $image] = $v;
            $car = Car::create([
                'category_id' => $categories->firstWhere('name', $category)->id,
                'registration_number' => 'ASTRA-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'color' => $color,
                'seats' => $seats,
                'doors' => $doors,
                'fuel_type' => $fuel,
                'transmission' => $transmission,
                'daily_price' => $price,
                'mileage' => $mileage,
                'description' => $description,
                'operational_status' => 'available',
                'is_active' => true,
            ]);
            CarImage::create([
                'car_id' => $car->id,
                'path' => $image,
                'alt_text' => "$brand $model",
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        $this->call(DemoFleetSeeder::class);
        $this->call(AgencySettingsSeeder::class);

        $car = Car::first();

        // Add examples for each workflow state without creating blocking overlaps.
        Reservation::create([
            'reservation_number' => 'AST-DEMO-001',
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => now()->addDays(14)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'rental_days' => 4,
            'daily_price' => $car->daily_price,
            'total_amount' => $car->daily_price * 4,
            'status' => 'pending',
            'client_message' => 'Merci de confirmer la disponibilité.',
        ]);

        Reservation::create([
            'reservation_number' => 'AST-DEMO-002',
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(24)->toDateString(),
            'rental_days' => 4,
            'daily_price' => $car->daily_price,
            'total_amount' => $car->daily_price * 4,
            'status' => 'confirmed',
        ]);

        Reservation::create([
            'reservation_number' => 'AST-DEMO-003',
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(24)->toDateString(), // Overlaps with confirmed above, which is why it was rejected
            'rental_days' => 4,
            'daily_price' => $car->daily_price,
            'total_amount' => $car->daily_price * 4,
            'status' => 'rejected',
            'internal_note' => 'Rejet automatique : période déjà réservée.',
        ]);

        Reservation::create([
            'reservation_number' => 'AST-DEMO-004',
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => now()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(32)->toDateString(),
            'rental_days' => 2,
            'daily_price' => $car->daily_price,
            'total_amount' => $car->daily_price * 2,
            'status' => 'cancelled',
        ]);

        // 5. Completed Reservation (Past)
        Reservation::create([
            'reservation_number' => 'AST-DEMO-005',
            'user_id' => $client->id,
            'car_id' => $car->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(6)->toDateString(),
            'rental_days' => 4,
            'daily_price' => $car->daily_price,
            'total_amount' => $car->daily_price * 4,
            'status' => 'completed',
        ]);
    }
}
