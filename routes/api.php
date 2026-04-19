<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\ListSubscriberController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('accounts', [AccountController::class, 'index']);
        Route::post('accounts/switch/{organization}', [AccountController::class, 'switch']);

        // Subscribers — import must be registered before the resource routes
        Route::post('subscribers/import', [SubscriberController::class, 'import']);
        Route::apiResource('subscribers', SubscriberController::class);

        // Lists
        Route::apiResource('lists', ListController::class);
        Route::post('lists/{list}/subscribers', [ListSubscriberController::class, 'store']);
        Route::delete('lists/{list}/subscribers/{subscriber}', [ListSubscriberController::class, 'destroy']);

        // Messages — send must be registered before the resource routes
        Route::post('messages/{message}/send', [MessageController::class, 'send']);
        Route::apiResource('messages', MessageController::class);

        // Keywords
        Route::apiResource('keywords', KeywordController::class);
    });
});
