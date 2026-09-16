<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Provides a stable public representation of a vehicle. */
class CarResource extends JsonResource
{
    /** Converts a car and its loaded relations into the stable public JSON contract. */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'brand' => $this->brand, 'model' => $this->model, 'category' => $this->category, 'year' => $this->year, 'color' => $this->color, 'seats' => $this->seats, 'doors' => $this->doors, 'fuel_type' => $this->fuel_type, 'transmission' => $this->transmission, 'daily_price' => (float) $this->daily_price, 'mileage' => $this->mileage, 'description' => $this->description, 'operational_status' => $this->operational_status, 'images' => $this->images];
    }
}
