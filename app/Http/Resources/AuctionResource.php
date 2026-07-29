<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Auctions\Services\DutchPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Auction */
final class AuctionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'currency' => $this->currency,
            'starting_price_minor' => $this->starting_price_minor,
            'current_price_minor' => $this->type === AuctionType::Dutch && $this->status === AuctionStatus::Active
                ? app(DutchPriceCalculator::class)->currentPrice($this->resource)
                : $this->current_price_minor,
            'minimum_increment_minor' => $this->minimum_increment_minor,
            'reserve_price_minor' => $this->when(
                $this->status === AuctionStatus::Completed
                    || $request->user()?->getKey() === $this->seller_id
                    || ($request->user()?->hasPermission('auction.approve') ?? false),
                $this->reserve_price_minor,
            ),
            'buy_now_price_minor' => $this->buy_now_price_minor,
            'price_decrement_minor' => $this->price_decrement_minor,
            'price_decrement_interval_seconds' => $this->price_decrement_interval_seconds,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'anti_sniping_enabled' => $this->anti_sniping_enabled,
            'extension_count' => $this->extension_count,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'is_private' => $this->is_private,
            'seller' => [
                'id' => $this->seller->id,
                'display_name' => $this->seller->display_name,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'bid_count' => $this->whenCounted('bids'),
            'media' => AuctionMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
