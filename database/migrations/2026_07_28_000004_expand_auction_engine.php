<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->unsignedBigInteger('buy_now_price_minor')->nullable()->after('reserve_price_minor');
            $table->unsignedBigInteger('price_decrement_minor')->nullable()->after('buy_now_price_minor');
            $table->unsignedInteger('price_decrement_interval_seconds')->nullable()->after('price_decrement_minor');
            $table->boolean('anti_sniping_enabled')->default(true)->after('ends_at');
            $table->unsignedSmallInteger('extension_count')->default(0)->after('anti_sniping_enabled');
            $table->unsignedSmallInteger('max_extensions')->default(10)->after('extension_count');
            $table->timestamp('closed_at')->nullable()->after('max_extensions');
        });

        Schema::table('bids', function (Blueprint $table): void {
            $table->unsignedBigInteger('maximum_bid_minor')->nullable()->after('amount_minor');
            $table->boolean('is_automatic')->default(false)->after('maximum_bid_minor');
        });

        Schema::create('watchlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('auction_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('notify_bid')->default(false);
            $table->boolean('notify_closing')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'auction_id']);
        });

        Schema::create('auction_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type', 48)->index();
            $table->string('title', 160);
            $table->text('message');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('reviewed_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 24)->default('published')->index();
            $table->timestamps();
            $table->unique(['auction_id', 'reviewer_id']);
            $table->index(['reviewed_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('auction_notifications');
        Schema::dropIfExists('watchlists');

        Schema::table('bids', function (Blueprint $table): void {
            $table->dropColumn(['maximum_bid_minor', 'is_automatic']);
        });

        Schema::table('auctions', function (Blueprint $table): void {
            $table->dropColumn([
                'buy_now_price_minor',
                'price_decrement_minor',
                'price_decrement_interval_seconds',
                'anti_sniping_enabled',
                'extension_count',
                'max_extensions',
                'closed_at',
            ]);
        });
    }
};
