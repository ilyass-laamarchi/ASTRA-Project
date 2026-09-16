<?php

// REST routes are grouped by public access, authenticated client ownership, and staff role.

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ManagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

// Public surface: authentication, active fleet, availability, and signed provider webhook.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{car}', [CarController::class, 'show']);
Route::get('/categories', [CarController::class, 'categories']);
Route::get('/cars/{car}/availability', [CarController::class, 'availability']);
Route::post('/cars/{car}/availability', [CarController::class, 'availability']);
Route::get('/cars/{car}/unavailable-periods', [CarController::class, 'unavailablePeriods']);
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/profile', [AuthController::class, 'update']);
    Route::post('/profile/avatar', [AuthController::class, 'avatar']);
    Route::put('/password', [AuthController::class, 'password']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:client')->group(function (): void {
        Route::get('/my-reservations', [ReservationController::class, 'mine']);
        Route::post('/my-reservations', [ReservationController::class, 'store']);
        Route::get('/my-reservations/{reservation}', [ReservationController::class, 'showMine']);
        Route::patch('/my-reservations/{reservation}/cancel', [ReservationController::class, 'cancelMine']);
        Route::get('/payment-configuration', [PaymentController::class, 'configuration']);
        Route::post('/reservations/{reservation}/checkout', [PaymentController::class, 'checkout']);
        Route::get('/my-payments', [PaymentController::class, 'mine']);
        Route::get('/my-payments/{payment}', [PaymentController::class, 'showMine']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);
    });

    foreach (['owner' => 'owner,admin', 'admin' => 'admin'] as $prefix => $roles) {
        Route::prefix($prefix)->middleware("role:$roles")->group(function () use ($prefix): void {
            Route::get('/dashboard', [ManagementController::class, 'dashboard']);
            Route::get('/categories', [ManagementController::class, 'categories']);
            Route::post('/categories', [ManagementController::class, 'saveCategory']);
            Route::put('/categories/{category}', [ManagementController::class, 'saveCategory']);
            Route::patch('/categories/{category}/activation', [ManagementController::class, 'categoryActivation']);
            Route::get('/cars', [ManagementController::class, 'cars']);
            Route::get('/cars/{car}', [ManagementController::class, 'showCar']);
            Route::post('/cars', [ManagementController::class, 'saveCar']);
            Route::put('/cars/{car}', [ManagementController::class, 'saveCar']);
            Route::patch('/cars/{car}/activation', [ManagementController::class, 'carActivation']);
            Route::patch('/cars/{car}/operational-status', [ManagementController::class, 'operationalStatus']);
            Route::post('/cars/{car}/images', [ManagementController::class, 'uploadImages']);
            Route::patch('/cars/{car}/images/{image}/primary', [ManagementController::class, 'primaryImage']);
            Route::patch('/cars/{car}/images/reorder', [ManagementController::class, 'reorderImages']);
            Route::delete('/cars/{car}/images/{image}', [ManagementController::class, 'deleteImage']);
            Route::get('/clients', [ManagementController::class, 'clients']);
            Route::get('/clients/{user}', [ManagementController::class, 'showClient']);
            Route::get('/reservations', [ReservationController::class, 'staffIndex']);
            Route::get('/reservations/{reservation}', [ReservationController::class, 'staffShow']);
            Route::patch('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
            Route::patch('/reservations/{reservation}/reject', [ReservationController::class, 'reject']);
            Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
            Route::patch('/reservations/{reservation}/complete', [ReservationController::class, 'complete']);
            Route::get('/payments', [PaymentController::class, 'staffIndex']);
            Route::get('/payments/{payment}', [PaymentController::class, 'staffShow']);
            if ($prefix === 'admin') {
                Route::get('/staff', [ManagementController::class, 'staff']);
                Route::post('/staff', [ManagementController::class, 'createStaff']);
                Route::get('/staff/{user}', [ManagementController::class, 'showStaff']);
                Route::put('/staff/{user}', [ManagementController::class, 'updateStaff']);
                Route::patch('/staff/{user}/activation', [ManagementController::class, 'activation']);
                Route::patch('/clients/{user}/activation', [ManagementController::class, 'activation']);
                Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
                Route::get('/settings', [SettingsController::class, 'show']);
                Route::put('/settings', [SettingsController::class, 'update']);
                Route::get('/settings/payment-status', [SettingsController::class, 'paymentStatus']);
            }
        });
    }
});
