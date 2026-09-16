<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Events\PaymentStatusChanged;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Orchestrates checkout, signed webhooks and refund state without trusting the browser. */
class PaymentService
{
    /** Receives the provider adapter and the business services checkout depends on. */
    public function __construct(private PaymentGatewayInterface $gateway, private CarAvailabilityService $availability, private NotificationService $notifications) {}

    /** Returns true only when every required payment setting exists. */
    public function isCheckoutConfigured(): bool
    {
        return config('services.payment.provider') === 'stripe'
            && filled(config('services.payment.public'))
            && filled(config('services.payment.secret'))
            && filled(config('services.payment.webhook_secret'));
    }

    /** Creates or reuses a checkout session for an eligible reservation. */
    public function checkout(Reservation $reservation): array
    {
        abort_unless($reservation->status === 'confirmed', 422, 'Seule une réservation confirmée peut être payée.');
        abort_unless($this->isCheckoutConfigured(), 503, 'Paiement temporairement indisponible');

        return DB::transaction(function () use ($reservation): array {
            // Lock the reservation so two checkout requests cannot create competing payments.
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            abort_unless($lockedReservation->status === 'confirmed', 409, 'Le statut de cette réservation a changé. Actualisez la page.');
            $car = Car::query()->lockForUpdate()->findOrFail($lockedReservation->car_id);
            [$start, $end] = $this->availability->normalizeRange($lockedReservation->start_date->toDateString(), $lockedReservation->end_date->toDateString());
            abort_unless($this->availability->isAvailable($car, $start, $end, $lockedReservation->id), 409, 'Cette réservation n’est plus valide pour les dates sélectionnées.');

            $payment = Payment::query()->lockForUpdate()
                ->where('reservation_id', $lockedReservation->id)
                ->whereIn('status', ['pending', 'processing', 'paid'])
                ->latest()->first();
            abort_if($payment?->status === 'paid', 409, 'Cette réservation est déjà payée.');

            // Use the amount stored by Laravel; the browser cannot choose a payment amount.
            $payment ??= Payment::create([
                'reservation_id' => $lockedReservation->id,
                'user_id' => $lockedReservation->user_id,
                'payment_reference' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'provider' => config('services.payment.provider', 'stripe'),
                'amount' => $lockedReservation->total_amount,
                'currency' => 'MAD',
                'status' => 'pending',
            ]);
            if ($payment->provider_session_id) {
                return ['payment' => $payment, 'checkout_url' => $payment->metadata['checkout_url'] ?? null];
            }

            $result = $this->gateway->createCheckout($payment->load('reservation'));
            $payment->update(['provider_session_id' => $result['session_id'], 'status' => 'processing', 'metadata' => ['checkout_url' => $result['checkout_url']]]);
            rescue(fn () => event(new PaymentStatusChanged($payment->fresh())), report: true);

            return ['payment' => $payment->fresh(), 'checkout_url' => $result['checkout_url']];
        }, 3);
    }

    /** Verifies a signed provider event before recording a paid status. */
    public function webhook(string $payload, string $signature): Payment
    {
        $event = $this->gateway->parseWebhook($payload, $signature);
        $payment = DB::transaction(function () use ($event): Payment {
            $payment = Payment::query()->lockForUpdate()->where('provider_session_id', $event['session_id'])->firstOrFail();
            if ($payment->status === 'paid') {
                return $payment;
            }
            abort_unless($event['type'] === 'checkout.session.completed', 422, 'Événement ignoré.');
            abort_unless((float) $event['amount'] === (float) $payment->amount && $event['currency'] === $payment->currency, 422, 'Montant ou devise invalide.');
            $payment->update(['status' => 'paid', 'provider_payment_id' => $event['payment_intent'], 'payment_method' => 'card', 'paid_at' => now()]);

            return $payment;
        });
        rescue(fn () => event(new PaymentStatusChanged($payment)), report: true);
        $this->notifications->send($payment->user, 'payment.paid', 'Paiement confirmé', 'Le paiement '.$payment->payment_reference.' a été confirmé.', ['payment_id' => $payment->id, 'status' => 'paid']);

        return $payment;
    }

    /** Requests a provider refund before recording the local refunded state. */
    public function refund(Payment $payment): Payment
    {
        abort_unless($payment->status === 'paid', 422, 'Seul un paiement payé peut être remboursé.');
        $this->gateway->refund($payment);
        $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
        rescue(fn () => event(new PaymentStatusChanged($payment->fresh())), report: true);
        $this->notifications->send($payment->user, 'payment.refunded', 'Paiement remboursé', 'Le paiement '.$payment->payment_reference.' a été remboursé.', ['payment_id' => $payment->id, 'status' => 'refunded']);

        return $payment;
    }
}
