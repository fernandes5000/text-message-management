<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\ListSubscriberController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PollController;
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

        // Inbox / Conversations
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('conversations/{conversation}/reply', [ConversationController::class, 'reply']);
        Route::patch('conversations/{conversation}/done', [ConversationController::class, 'markDone']);
        Route::patch('conversations/{conversation}/unread', [ConversationController::class, 'markUnread']);

        // Polls
        Route::get('polls', [PollController::class, 'index']);
        Route::post('polls', [PollController::class, 'store']);
        Route::get('polls/{poll}', [PollController::class, 'show']);
        Route::delete('polls/{poll}', [PollController::class, 'destroy']);

        // Integrations
        Route::get('integrations', [IntegrationController::class, 'index']);
        Route::post('integrations/{integration}/connect', [IntegrationController::class, 'connect']);
        Route::post('integrations/{integration}/disconnect', [IntegrationController::class, 'disconnect']);
    });
});
