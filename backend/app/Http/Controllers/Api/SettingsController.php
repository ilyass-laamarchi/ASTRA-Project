<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages administrator-owned agency settings and masked payment readiness. */
class SettingsController extends Controller
{
    /** Receives the payment service used to report provider readiness. */
    public function __construct(private PaymentService $payments) {}

    /** Returns persisted agency settings merged with Tanger defaults. */
    public function show(): JsonResponse
    {
        return response()->json(['data' => array_merge([
            'agency_name' => 'ASTRA Location Tanger', 'agency_email' => 'contact@astra.ma',
            'agency_phone' => '+212 5 39 00 00 00', 'agency_address' => 'Tanger, Maroc',
            'currency' => 'MAD', 'timezone' => 'Africa/Casablanca', 'booking_notice_hours' => '24',
        ], ApplicationSetting::values())]);
    }

    /** Validates and upserts administrator-controlled agency settings. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agency_name' => ['required', 'string', 'max:120'], 'agency_email' => ['required', 'email', 'max:160'],
            'agency_phone' => ['required', 'string', 'max:30'], 'agency_address' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'in:MAD'], 'timezone' => ['required', 'timezone'],
            'booking_notice_hours' => ['required', 'integer', 'between:0,168'],
        ]);
        foreach ($data as $key => $value) {
            ApplicationSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return $this->show();
    }

    /** Reports masked Stripe configuration state without exposing secrets. */
    public function paymentStatus(): JsonResponse
    {
        $configured = $this->payments->isCheckoutConfigured();

        return response()->json(['data' => [
            'configured' => $configured,
            'mode' => str_starts_with((string) config('services.payment.public'), 'pk_live_') ? 'live' : 'test',
            'publishable_key' => filled(config('services.payment.public')) ? substr((string) config('services.payment.public'), 0, 8).'••••••••' : null,
            'webhook_configured' => filled(config('services.payment.webhook_secret')),
            'message' => $configured ? 'Stripe est prêt pour les paiements.' : 'Ajoutez les clés Stripe au serveur pour activer le paiement.',
        ]]);
    }
}
