<?php

namespace App\Events;

use App\Models\AstraNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Broadcasts a newly stored notification to its recipient's private channel. */
class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /** Receives the stored notification that will be delivered. */
    public function __construct(public AstraNotification $notification) {}

    /** Restricts delivery to the recipient's authenticated private channel. */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->notification->user_id)];
    }

    /** Returns the event name listened to by the Vue workspace. */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /** Returns the notification payload consumed by the frontend. */
    public function broadcastWith(): array
    {
        return ['notification' => $this->notification->toArray()];
    }
}
