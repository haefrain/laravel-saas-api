<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public auth endpoints are throttled to blunt credential-stuffing.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});
