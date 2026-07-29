<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Auctions\Actions\PurchaseAuctionAction;
use App\Application\Reviews\Actions\SubmitReviewAction;
use App\Application\Watchlists\Actions\UpdateWatchlistAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuctionInteractionController extends Controller
{
    public function purchase(Request $request, Auction $auction, PurchaseAuctionAction $action): AuctionResource
    {
        /** @var User $buyer */
        $buyer = $request->user();

        return new AuctionResource($action->execute($auction, $buyer)->load(['seller', 'category'])->loadCount('bids'));
    }

    public function watch(Request $request, Auction $auction, UpdateWatchlistAction $action): JsonResponse
    {
        $validated = $request->validate([
            'watching' => ['required', 'boolean'],
            'notify_bid' => ['sometimes', 'boolean'],
            'notify_closing' => ['sometimes', 'boolean'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $watchlist = $action->execute(
            $auction,
            $user,
            $validated['watching'],
            $validated['notify_bid'] ?? false,
            $validated['notify_closing'] ?? true,
        );

        return response()->json(['data' => $watchlist], Response::HTTP_OK);
    }

    public function review(Request $request, Auction $auction, SubmitReviewAction $action): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $review = $action->execute($auction, $user, $validated['rating'], $validated['comment'] ?? null);

        return response()->json(['data' => $review], Response::HTTP_CREATED);
    }
}
