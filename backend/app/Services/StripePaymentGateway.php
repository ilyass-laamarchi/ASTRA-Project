<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Stripe\StripeClient;
use Stripe\Webhook;

/** Adapter for Stripe-hosted Checkout; ASTRA never receives card fields. */
class StripePaymentGateway implements PaymentGatewayInterface
{
    /** Builds the Stripe SDK client from the server-side secret key. */
    private function client(): StripeClient
    {
        $key = (string) config('services.payment.secret');
        abort_if($key === '', 503, 'Le paiement en ligne n’est pas encore configuré.');

        return new StripeClient($key);
    }

    /** Creates a Stripe-hosted Checkout session for one stored payment. */
    public function createCheckout(Payment $payment): array
    {
        $session = $this->client()->checkout->sessions->create(['mode' => 'payment', 'success_url' => config('services.payment.success_url').'?payment='.$payment->id, 'cancel_url' => config('services.payment.cancel_url'), 'client_reference_id' => $payment->payment_reference, 'metadata' => ['payment_id' => (string) $payment->id], 'line_items' => [['quantity' => 1, 'price_data' => ['currency' => strtolower($payment->currency), 'unit_amount' => (int) round((float) $payment->amount * 100), 'product_data' => ['name' => 'Réservation '.$payment->reservation->reservation_number]]]]]);

        return ['session_id' => $session->id, 'checkout_url' => $session->url];
    }

    /** Validates a webhook signature and returns the fields PaymentService uses. */
    public function parseWebhook(string $payload, string $signature): array
    {
        $event = Webhook::constructEvent($payload, $signature, (string) config('services.payment.webhook_secret'));
        $object = $event->data->object;

        return ['event_id' => $event->id, 'type' => $event->type, 'session_id' => $object->id ?? null, 'payment_intent' => $object->payment_intent ?? null, 'amount' => (isset($object->amount_total) ? $object->amount_total / 100 : null), 'currency' => isset($object->currency) ? strtoupper($object->currency) : null];
    }

    /** Requests a refund for the verified provider payment identifier. */
    public function refund(Payment $payment): array
    {
        $refund = $this->client()->refunds->create(['payment_intent' => $payment->provider_payment_id, 'metadata' => ['payment_id' => (string) $payment->id]]);

        return ['id' => $refund->id, 'status' => $refund->status];
    }
}
