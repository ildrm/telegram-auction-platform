<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'payload',
        'read_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'immutable_datetime',
        ];
    }
}
