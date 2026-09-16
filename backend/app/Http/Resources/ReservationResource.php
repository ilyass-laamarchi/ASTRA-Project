<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Serializes calculated reservation fields and related car/payment state. */
class ReservationResource extends JsonResource
{
    /** Returns client-safe reservation JSON and includes private notes only for staff. */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'reservation_number' => $this->reservation_number, 'car' => $this->whenLoaded('car'), 'user' => $this->when($request->user()?->isStaff(), $this->whenLoaded('user')), 'start_date' => $this->start_date?->toDateString(), 'end_date' => $this->end_date?->toDateString(), 'rental_days' => $this->rental_days, 'daily_price' => (float) $this->daily_price, 'total_amount' => (float) $this->total_amount, 'currency' => 'MAD', 'status' => $this->status, 'client_message' => $this->client_message, 'internal_note' => $this->when($request->user()?->isStaff(), $this->internal_note), 'rejection_reason' => $this->when($this->status === 'rejected', $this->internal_note), 'payments' => $this->whenLoaded('payments'), 'created_at' => $this->created_at];
    }
}
