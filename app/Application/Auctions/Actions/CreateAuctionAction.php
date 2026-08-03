<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Application\Auctions\Data\CreateAuctionData;
use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Auctions\Events\AuctionCreated;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateAuctionAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(User $seller, CreateAuctionData $data): Auction
    {
        if (Gate::forUser($seller)->denies('create', Auction::class)) {
            throw new AuthorizationException;
        }

        if ($data->endsAt->lessThanOrEqualTo($data->startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => [__('auction.ends_after_start')],
            ]);
        }

        if ($data->minimumIncrementMinor < 1) {
            throw ValidationException::withMessages([
                'minimum_increment_minor' => [__('auction.minimum_increment_positive')],
            ]);
        }

        if ($data->startingPriceMinor < 0 || $data->maxExtensions < 0 || $data->maxExtensions > 100) {
            throw ValidationException::withMessages([
                'starting_price_minor' => [__('auction.invalid_configuration')],
            ]);
        }

        if ($data->reservePriceMinor !== null) {
            $validReserve = $data->type === AuctionType::Reverse
                ? $data->reservePriceMinor <= $data->startingPriceMinor
                : $data->reservePriceMinor >= $data->startingPriceMinor;

            if (! $validReserve) {
                throw ValidationException::withMessages([
                    'reserve_price_minor' => [__('auction.invalid_reserve_price')],
                ]);
            }
        }

        if (in_array($data->type, [AuctionType::BuyNow, AuctionType::Hybrid], true)
            && ($data->buyNowPriceMinor === null || $data->buyNowPriceMinor < $data->startingPriceMinor)) {
            throw ValidationException::withMessages([
                'buy_now_price_minor' => [__('auction.buy_now_price_required')],
            ]);
        }

        if ($data->type === AuctionType::Dutch
            && ($data->priceDecrementMinor === null
                || $data->priceDecrementMinor < 1
                || $data->priceDecrementMinor >= $data->startingPriceMinor
                || $data->priceDecrementIntervalSeconds === null
                || $data->priceDecrementIntervalSeconds < 10)) {
            throw ValidationException::withMessages([
                'price_decrement_minor' => [__('auction.dutch_configuration_required')],
            ]);
        }

        return DB::transaction(function () use ($seller, $data): Auction {
            $auction = Auction::query()->create([
                'seller_id' => $seller->getKey(),
                'category_id' => $data->categoryId,
                'title' => $data->title,
                'slug' => $this->uniqueSlug($data->title),
                'description' => $data->description,
                'type' => $data->type,
                'status' => AuctionStatus::Draft,
                'currency' => $data->currency,
                'starting_price_minor' => $data->startingPriceMinor,
                'current_price_minor' => $data->startingPriceMinor,
                'minimum_increment_minor' => $data->minimumIncrementMinor,
                'reserve_price_minor' => $data->reservePriceMinor,
                'buy_now_price_minor' => $data->buyNowPriceMinor,
                'price_decrement_minor' => $data->priceDecrementMinor,
                'price_decrement_interval_seconds' => $data->priceDecrementIntervalSeconds,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'anti_sniping_enabled' => $data->antiSnipingEnabled,
                'max_extensions' => $data->maxExtensions,
                'is_private' => $data->isPrivate,
            ]);

            $this->auditLogger->record(
                actor: $seller,
                action: 'auction.created',
                subject: $auction,
                before: null,
                after: $auction->attributesToArray(),
                metadata: null,
            );

            AuctionCreated::dispatch($auction->getKey(), $seller->getKey());

            return $auction;
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'auction';

        do {
            $slug = $base.'-'.Str::lower(Str::random(8));
        } while (Auction::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
