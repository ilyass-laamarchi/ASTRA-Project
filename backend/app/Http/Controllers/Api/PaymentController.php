<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Enforces ownership for checkout/receipt and role separation for refunds. */
class PaymentController extends Controller
{
    /** Receives the service that owns provider and payment-state rules. */
    public function __construct(private PaymentService $payments) {}

    /** Tells clients whether the required Stripe configuration is complete. */
    public function configuration(): JsonResponse
    {
        return response()->json(['checkout_available' => $this->payments->isCheckoutConfigured(), 'message' => $this->payments->isCheckoutConfigured() ? null : 'Paiement temporairement indisponible']);
    }

    /** Starts checkout only for the authenticated client's reservation. */
    public function checkout(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return response()->json($this->payments->checkout($reservation));
    }

    /** Lists only payments owned by the authenticated client. */
    public function mine(Request $request)
    {
        return response()->json(['data' => $request->user()->payments()->with('reservation.car.images')->latest()->paginate(15)]);
    }

    /** Returns one payment only to its owning client. */
    public function showMine(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return response()->json(['data' => $payment->load('reservation.car.images')]);
    }

    /** Generates a simple receipt for an owned, verified paid payment. */
    public function receipt(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->status === 'paid', 403);
        $text = "ASTRA\nReçu: {$payment->payment_reference}\nMontant: {$payment->amount} {$payment->currency}\nRéservation: {$payment->reservation->reservation_number}\n";

        return response($text)->header('Content-Type', 'text/plain; charset=UTF-8')->header('Content-Disposition', 'attachment; filename="recu-'.$payment->payment_reference.'.txt"');
    }

    /** Lists provider payments for authorized staff dashboards. */
    public function staffIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => Payment::with(['reservation.car', 'user'])->when($request->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(20)]);
    }

    /** Returns one payment with its reservation, car, and client to staff. */
    public function staffShow(Payment $payment): JsonResponse
    {
        return response()->json(['data' => $payment->load(['reservation.car', 'user'])]);
    }

    /** Delegates an administrator-authorized refund to the payment service. */
    public function refund(Payment $payment): JsonResponse
    {
        return response()->json(['data' => $this->payments->refund($payment)]);
    }

    /** Verifies a Stripe webhook and never trusts a frontend success redirect. */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payment = $this->payments->webhook($request->getContent(), (string) $request->header('Stripe-Signature'));

            return response()->json(['received' => true, 'payment' => $payment->id]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Signature ou événement invalide.'], 400);
        }
    }
}
