<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use Illuminate\Database\Seeder;

/** Maintains the idempotent Moroccan presentation fleet without replacing car IDs. */
class DemoFleetSeeder extends Seeder
{
    /** Upserts demonstration cars and their primary public images. */
    public function run(): void
    {
        $vehicles = [
            ['ASTRA-007', 'Toyota', 'Yaris', 'Citadine', 2025, 'Rouge profond', 5, 5, 'gasoline', 'automatic', 420, 7500, 'Citadine automatique vive et économique, idéale pour Tanger et les déplacements régionaux.', '/assets/images/toyota-yaris-cutout.png'],
            ['ASTRA-008', 'Kia', 'Picanto', 'Citadine', 2024, 'Argent métallisé', 5, 5, 'gasoline', 'manual', 330, 14500, 'Format compact, conduite simple et consommation maîtrisée pour les trajets urbains.', '/assets/images/kia-picanto-cutout.png'],
            ['ASTRA-009', 'Volkswagen', 'Golf', 'Berline', 2024, 'Noir intense', 5, 5, 'diesel', 'automatic', 680, 19000, 'Une compacte polyvalente, précise et confortable pour la ville comme l’autoroute.', '/assets/images/volkswagen-golf-cutout.png'],
            ['ASTRA-010', 'Skoda', 'Octavia', 'Berline', 2025, 'Bleu nuit', 5, 5, 'diesel', 'automatic', 720, 8200, 'Habitabilité généreuse, grand coffre et confort durable pour les longs parcours.', '/assets/images/skoda-octavia-cutout.png'],
            ['ASTRA-011', 'Toyota', 'RAV4', 'SUV', 2025, 'Vert forêt', 5, 5, 'hybrid', 'automatic', 950, 5400, 'SUV hybride spacieux et silencieux, adapté aux escapades comme aux déplacements professionnels.', '/assets/images/toyota-rav4-cutout.png'],
            ['ASTRA-012', 'Range Rover', 'Evoque', 'Prestige', 2024, 'Cuivre métallisé', 5, 5, 'diesel', 'automatic', 1450, 16000, 'SUV compact de prestige au style affirmé, avec une finition haut de gamme.', '/assets/images/range-rover-evoque-cutout.png'],
            ['ASTRA-013', 'Dacia', 'Sandero', 'Citadine', 2025, 'Bleu métallisé', 5, 5, 'gasoline', 'manual', 360, 9000, 'Citadine moderne, spacieuse et économique, adaptée aux trajets quotidiens autour de Tanger.', '/assets/images/dacia-sandero-cutout.png'],
            ['ASTRA-014', 'Peugeot', '208', 'Citadine', 2025, 'Blanc nacré', 5, 5, 'gasoline', 'automatic', 470, 6500, 'Citadine automatique élégante et agréable, idéale en ville comme sur la côte.', '/assets/images/peugeot-208-cutout.png'],
            ['ASTRA-015', 'Opel', 'Corsa', 'Citadine', 2024, 'Orange métallisé', 5, 5, 'gasoline', 'manual', 390, 12000, 'Compacte maniable et sobre offrant un bon équilibre entre confort et budget.', '/assets/images/opel-corsa-cutout.png'],
            ['ASTRA-016', 'Dacia', 'Logan', 'Berline', 2024, 'Gris graphite', 5, 4, 'diesel', 'manual', 460, 18000, 'Berline pratique au coffre généreux, adaptée aux familles et aux longs trajets.', '/assets/images/dacia-logan-cutout.png'],
            ['ASTRA-017', 'Renault', 'Kardian', 'SUV', 2025, 'Vert profond', 5, 5, 'gasoline', 'automatic', 650, 4000, 'Crossover automatique récent, polyvalent pour la ville et les routes du nord du Maroc.', '/assets/images/renault-kardian-cutout.png'],
            ['ASTRA-018', 'Hyundai', 'Tucson', 'SUV', 2024, 'Argent métallisé', 5, 5, 'diesel', 'automatic', 900, 14000, 'SUV familial confortable, spacieux et bien équipé pour les déplacements longue distance.', '/assets/images/hyundai-tucson-cutout.png'],
            ['ASTRA-019', 'Kia', 'Sportage', 'SUV', 2025, 'Bleu profond', 5, 5, 'hybrid', 'automatic', 980, 6000, 'SUV hybride moderne combinant confort, espace et consommation maîtrisée.', '/assets/images/kia-sportage-cutout.png'],
            ['ASTRA-020', 'Volkswagen', 'Tiguan', 'Prestige', 2025, 'Blanc perle', 5, 5, 'diesel', 'automatic', 1250, 5000, 'SUV premium polyvalent avec une finition raffinée et un excellent confort routier.', '/assets/images/volkswagen-tiguan-cutout.png'],
        ];

        foreach ($vehicles as $vehicle) {
            [$registration,$brand,$model,$categoryName,$year,$color,$seats,$doors,$fuel,$transmission,$price,$mileage,$description,$image] = $vehicle;
            $category = Category::firstOrCreate(['name' => $categoryName], ['description' => 'Catégorie ASTRA', 'is_active' => true]);
            $car = Car::updateOrCreate(['registration_number' => $registration], [
                'category_id' => $category->id, 'brand' => $brand, 'model' => $model, 'year' => $year, 'color' => $color,
                'seats' => $seats, 'doors' => $doors, 'fuel_type' => $fuel, 'transmission' => $transmission,
                'daily_price' => $price, 'mileage' => $mileage, 'description' => $description,
                'operational_status' => 'available', 'is_active' => true,
            ]);
            CarImage::updateOrCreate(
                ['car_id' => $car->id, 'is_primary' => true],
                ['path' => $image, 'alt_text' => "$brand $model", 'sort_order' => 0],
            );
        }
    }
}
