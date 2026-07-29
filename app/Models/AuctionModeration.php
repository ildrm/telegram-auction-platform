<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Moderation\Enums\ModerationDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionModeration extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['auction_id', 'moderator_id', 'decision', 'reason'];

    /** @return BelongsTo<Auction, $this> */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    protected function casts(): array
    {
        return [
            'decision' => ModerationDecision::class,
            'created_at' => 'immutable_datetime',
        ];
    }
}
