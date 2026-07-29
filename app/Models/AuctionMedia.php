<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionMedia extends Model
{
    protected $table = 'auction_media';

    protected $fillable = [
        'auction_id',
        'uploaded_by',
        'disk',
        'original_path',
        'derivatives',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'checksum_sha256',
        'sort_order',
        'is_primary',
        'processing_status',
        'processing_error',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function casts(): array
    {
        return [
            'derivatives' => 'array',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }
}
