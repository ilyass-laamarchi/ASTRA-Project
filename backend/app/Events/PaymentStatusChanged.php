<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Private event publishes safe payment state, not provider metadata or card data. */
class PaymentStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $paymentId;

    public string $reference;

    public string $status;

    public string $updatedAt;

    private int $userId;

    /** Copies safe payment state without provider metadata or card details. */
    public function __construct(Payment $payment)
    {
        $this->paymentId = $payment->id;
        $this->reference = $payment->payment_reference;
        $this->status = $payment->status;
        $this->updatedAt = now()->toIso8601String();
        $this->userId = $payment->user_id;
    }

    /** Sends the event to the owning user and the private staff operations channel. */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->userId}"), new PrivateChannel('operations')];
    }

    /** Returns the stable event name used by Echo listeners. */
    public function broadcastAs(): string
    {
        return 'PaymentStatusChanged';
    }
}
