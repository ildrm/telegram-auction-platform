<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Watchlist extends Model
{
    protected $fillable = [
        'user_id',
        'auction_id',
        'notify_bid',
        'notify_closing',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    protected function casts(): array
    {
        return [
            'notify_bid' => 'boolean',
            'notify_closing' => 'boolean',
        ];
    }
}
