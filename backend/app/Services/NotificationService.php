<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\AstraNotification;
use App\Models\User;

/** Creates private notifications and broadcasts them when realtime is available. */
class NotificationService
{
    /** Stores a notification when the recipient has enabled its channel. */
    public function send(User $user, string $type, string $title, string $message, array $data = []): ?AstraNotification
    {
        $preferences = $user->notification_preferences ?? ['reservations' => true, 'payments' => true, 'system' => true];
        $channel = str_starts_with($type, 'reservation.')
            ? 'reservations'
            : (str_starts_with($type, 'payment.') ? 'payments' : 'system');
        if (($preferences[$channel] ?? true) === false) {
            return null;
        }

        $notification = AstraNotification::create(compact('type', 'title', 'message', 'data') + ['user_id' => $user->id]);
        rescue(fn () => event(new NotificationCreated($notification)), report: true);

        return $notification;
    }

    /** Sends one operational notification to every active staff account. */
    public function staff(string $type, string $title, string $message, array $data = []): void
    {
        User::whereIn('role', ['owner', 'admin'])->where('is_active', true)->each(
            fn (User $user) => $this->send($user, $type, $title, $message, $data)
        );
    }
}
