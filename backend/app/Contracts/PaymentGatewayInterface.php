<?php

namespace App\Contracts;

use App\Models\Payment;

/** Provider boundary keeps reservation logic independent from Stripe. */
interface PaymentGatewayInterface
{
    /** Creates a hosted checkout session for a stored payment. */
    public function createCheckout(Payment $payment): array;

    /** Validates and normalizes a signed provider webhook. */
    public function parseWebhook(string $payload, string $signature): array;

    /** Requests a provider refund for a verified payment. */
    public function refund(Payment $payment): array;
}
