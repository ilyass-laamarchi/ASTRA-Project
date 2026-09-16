<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\StripePaymentGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

/** Registers ASTRA service bindings and frontend-aware password reset links. */
class AppServiceProvider extends ServiceProvider
{
    /** Binds the payment contract to the current Stripe implementation. */
    public function register(): void
    {
        // The domain depends on an interface so another Moroccan provider can replace Stripe.
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
    }

    /** Configures password-reset emails to open the Vue reset page. */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/').'/reset-password?token='.$token.'&email='.urlencode($user->email));
    }
}
