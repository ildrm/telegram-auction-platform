<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Auctions\Actions\CreateAuctionAction;
use App\Application\Auctions\Data\CreateAuctionData;
use App\Domain\Auctions\Enums\AuctionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateAuctionRequest;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class AuctionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $auctions = Auction::query()
            ->discoverable()
            ->with(['seller:id,display_name', 'category:id,name', 'media'])
            ->withCount('bids')
            ->orderBy('ends_at')
            ->paginate(20);

        return AuctionResource::collection($auctions);
    }

    public function show(Auction $auction): AuctionResource
    {
        $this->authorize('view', $auction);

        return new AuctionResource(
            $auction->load(['seller:id,display_name', 'category:id,name', 'media'])->loadCount('bids'),
        );
    }

    public function store(
        CreateAuctionRequest $request,
        CreateAuctionAction $action,
    ): Response {
        /** @var User $seller */
        $seller = $request->user();
        $validated = $request->validated();

        $auction = $action->execute(
            seller: $seller,
            data: new CreateAuctionData(
                categoryId: $validated['category_id'],
                title: $validated['title'],
                description: $validated['description'],
                type: AuctionType::from($validated['type']),
                currency: $validated['currency'],
                startingPriceMinor: $validated['starting_price_minor'],
                minimumIncrementMinor: $validated['minimum_increment_minor'],
                reservePriceMinor: $validated['reserve_price_minor'] ?? null,
                startsAt: CarbonImmutable::parse($validated['starts_at'])->utc(),
                endsAt: CarbonImmutable::parse($validated['ends_at'])->utc(),
                isPrivate: $validated['is_private'],
                buyNowPriceMinor: $validated['buy_now_price_minor'] ?? null,
                priceDecrementMinor: $validated['price_decrement_minor'] ?? null,
                priceDecrementIntervalSeconds: $validated['price_decrement_interval_seconds'] ?? null,
                antiSnipingEnabled: $validated['anti_sniping_enabled'] ?? true,
                maxExtensions: $validated['max_extensions'] ?? 10,
            ),
        );

        return (new AuctionResource(
            $auction->load(['seller:id,display_name', 'category:id,name', 'media'])->loadCount('bids'),
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
