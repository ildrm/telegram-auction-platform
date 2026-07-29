<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('disk', 32);
            $table->string('original_path');
            $table->json('derivatives')->nullable();
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->char('checksum_sha256', 64);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('processing_status', 24)->default('pending')->index();
            $table->text('processing_error')->nullable();
            $table->timestamps();
            $table->index(['auction_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_media');
    }
};
