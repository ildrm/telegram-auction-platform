<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('type', 24)->index();
            $table->string('status', 24)->index();
            $table->char('currency', 3);
            $table->unsignedBigInteger('starting_price_minor');
            $table->unsignedBigInteger('current_price_minor');
            $table->unsignedBigInteger('minimum_increment_minor');
            $table->unsignedBigInteger('reserve_price_minor')->nullable();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->boolean('is_private')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['category_id', 'status']);
        });

        Schema::create('bids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('bidder_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->timestamp('placed_at')->index();
            $table->timestamps();

            $table->index(['auction_id', 'amount_minor']);
            $table->index(['bidder_id', 'placed_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('telegram_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('categories');
    }
};
