<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuctionInteractionController;
use App\Http\Controllers\Api\AuctionMediaController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram/webhook', TelegramWebhookController::class)
    ->middleware(['telegram.webhook', 'throttle:telegram-webhook']);

Route::get('health', HealthController::class)->middleware('throttle:30,1');

Route::get('media/{media}/{variant}', [AuctionMediaController::class, 'show'])
    ->middleware('signed')
    ->name('auction-media.show');

Route::prefix('v1')->group(function (): void {
    Route::get('auctions', [AuctionController::class, 'index']);
    Route::get('auctions/{auction:slug}', [AuctionController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::post('auctions', [AuctionController::class, 'store']);
        Route::post('auctions/{auction:slug}/bids', [BidController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::post('auctions/{auction:slug}/purchase', [AuctionInteractionController::class, 'purchase'])
            ->middleware('throttle:10,1');
        Route::put('auctions/{auction:slug}/watchlist', [AuctionInteractionController::class, 'watch']);
        Route::post('auctions/{auction:slug}/reviews', [AuctionInteractionController::class, 'review'])
            ->middleware('throttle:10,1');
        Route::post('auctions/{auction:slug}/media', [AuctionMediaController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::delete('auction-media/{media}', [AuctionMediaController::class, 'destroy']);
    });
});
