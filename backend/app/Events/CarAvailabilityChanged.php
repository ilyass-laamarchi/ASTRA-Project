<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Public event contains calendar state only—never client or payment data. */
class CarAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    private int $carId;

    private string $startDate;

    private string $endDate;

    private string $availability;

    private string $updatedAt;

    /** Copies only the reservation dates and car identity needed by public calendars. */
    public function __construct(Reservation $reservation, string $availability)
    {
        $this->carId = $reservation->car_id;
        $this->startDate = $reservation->start_date->toDateString();
        $this->endDate = $reservation->end_date->toDateString();
        $this->availability = $availability;
        $this->updatedAt = now()->toIso8601String();
    }

    /** Broadcasts on the public availability channel for this car. */
    public function broadcastOn(): array
    {
        return [new Channel("cars.{$this->carId}.availability")];
    }

    /** Returns the stable event name used by Echo listeners. */
    public function broadcastAs(): string
    {
        return 'CarAvailabilityChanged';
    }

    /** Keep the public WebSocket payload deliberately small and calendar-only. */
    public function broadcastWith(): array
    {
        return [
            'car_id' => $this->carId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'availability' => $this->availability,
            'updated_at' => $this->updatedAt,
        ];
    }
}
