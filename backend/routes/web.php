<?php

use Illuminate\Support\Facades\Route;

// The backend is API-only; this small response confirms that the Laravel service is running.
Route::get('/', fn () => response()->json([
    'application' => 'ASTRA API',
    'status' => 'running',
]));
