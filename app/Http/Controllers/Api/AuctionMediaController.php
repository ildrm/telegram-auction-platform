<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Media\Actions\DeleteAuctionImageAction;
use App\Application\Media\Actions\UploadAuctionImageAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionMediaResource;
use App\Models\Auction;
use App\Models\AuctionMedia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuctionMediaController extends Controller
{
    public function store(Request $request, Auction $auction, UploadAuctionImageAction $action): AuctionMediaResource
    {
        $request->validate(['image' => ['required', 'file']]);
        /** @var User $user */
        $user = $request->user();

        return new AuctionMediaResource($action->execute($auction, $user, $request->file('image')));
    }

    public function show(AuctionMedia $media, string $variant): StreamedResponse
    {
        $path = $variant === 'original'
            ? $media->original_path
            : ($media->derivatives[$variant] ?? null);
        abort_if($path === null, 404);
        $mime = $variant === 'original' ? $media->mime_type : 'image/webp';

        return Storage::disk($media->disk)->response(
            $path,
            null,
            ['Content-Type' => $mime, 'Content-Disposition' => 'inline'],
        );
    }

    public function destroy(Request $request, AuctionMedia $media, DeleteAuctionImageAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($media, $user);

        return response()->noContent();
    }
}
