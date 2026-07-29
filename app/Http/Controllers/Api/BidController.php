<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Bids\Actions\PlaceBidAction;
use App\Application\Bids\Data\PlaceBidData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceBidRequest;
use App\Http\Resources\BidResource;
use App\Models\Auction;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class BidController extends Controller
{
    public function store(
        PlaceBidRequest $request,
        Auction $auction,
        PlaceBidAction $action,
    ): Response {
        /** @var User $bidder */
        $bidder = $request->user();
        $validated = $request->validated();

        $bid = $action->execute(
            auction: $auction,
            bidder: $bidder,
            data: new PlaceBidData(
                amountMinor: $validated['amount_minor'],
                currency: $validated['currency'],
                maximumBidMinor: $validated['maximum_bid_minor'] ?? null,
            ),
        );

        return (new BidResource($bid))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
