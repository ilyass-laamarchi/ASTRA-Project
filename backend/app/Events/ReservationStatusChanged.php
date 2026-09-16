<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Private reservation event updates the owning client and internal operations. */
class ReservationStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $reservationId;

    public string $reservationNumber;

    public string $status;

    public string $updatedAt;

    private int $userId;

    /** Copies safe reservation identity and status fields into the event. */
    public function __construct(Reservation $reservation)
    {
        $this->reservationId = $reservation->id;
        $this->reservationNumber = $reservation->reservation_number;
        $this->status = $reservation->status;
        $this->updatedAt = now()->toIso8601String();
        $this->userId = $reservation->user_id;
    }

    /** Sends the event to the owning user and the private staff operations channel. */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->userId}"), new PrivateChannel('operations')];
    }

    /** Returns the stable event name used by Echo listeners. */
    public function broadcastAs(): string
    {
        return 'ReservationStatusChanged';
    }
}
