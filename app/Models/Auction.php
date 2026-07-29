<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use Database\Factories\AuctionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Auction extends Model
{
    /** @use HasFactory<AuctionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'winner_id',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'title',
        'slug',
        'description',
        'type',
        'status',
        'currency',
        'starting_price_minor',
        'current_price_minor',
        'minimum_increment_minor',
        'reserve_price_minor',
        'buy_now_price_minor',
        'price_decrement_minor',
        'price_decrement_interval_seconds',
        'starts_at',
        'ends_at',
        'anti_sniping_enabled',
        'extension_count',
        'max_extensions',
        'closed_at',
        'is_private',
    ];

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** @return BelongsTo<User, $this> */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(AuctionMedia::class)->orderBy('sort_order');
    }

    /** @param Builder<Auction> $query */
    public function scopeDiscoverable(Builder $query): void
    {
        $query
            ->where('status', AuctionStatus::Active)
            ->where('is_private', false)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    protected function casts(): array
    {
        return [
            'type' => AuctionType::class,
            'status' => AuctionStatus::class,
            'starting_price_minor' => 'integer',
            'current_price_minor' => 'integer',
            'minimum_increment_minor' => 'integer',
            'reserve_price_minor' => 'integer',
            'buy_now_price_minor' => 'integer',
            'price_decrement_minor' => 'integer',
            'price_decrement_interval_seconds' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'anti_sniping_enabled' => 'boolean',
            'extension_count' => 'integer',
            'max_extensions' => 'integer',
            'closed_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'is_private' => 'boolean',
        ];
    }
}
