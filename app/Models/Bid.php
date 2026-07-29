<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'bidder_id',
        'currency',
        'amount_minor',
        'maximum_bid_minor',
        'is_automatic',
        'placed_at',
    ];

    /** @return BelongsTo<Auction, $this> */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'maximum_bid_minor' => 'integer',
            'is_automatic' => 'boolean',
            'placed_at' => 'immutable_datetime',
        ];
    }
}
