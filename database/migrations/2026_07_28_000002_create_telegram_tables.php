<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('update_id')->unique();
            $table->json('payload');
            $table->string('status', 24)->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('conversation', 80);
            $table->string('step', 80);
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('telegram_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedBigInteger('chat_id')->index();
            $table->string('method', 60);
            $table->json('payload');
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('telegram_message_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_deliveries');
        Schema::dropIfExists('conversation_states');
        Schema::dropIfExists('telegram_updates');
    }
};
