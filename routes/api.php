<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\TeamController;
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

        // Team-agnostic: list the caller's teams, create a new one (creator => owner).
        Route::get('teams', [TeamController::class, 'index']);
        Route::post('teams', [TeamController::class, 'store']);

        // Team-scoped: the tenant middleware gates membership and sets context.
        Route::middleware('tenant')->group(function () {
            Route::get('teams/{team}', [TeamController::class, 'show']);
            Route::patch('teams/{team}', [TeamController::class, 'update']);
            Route::delete('teams/{team}', [TeamController::class, 'destroy']);
        });
    });
});
