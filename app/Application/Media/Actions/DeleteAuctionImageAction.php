<?php

declare(strict_types=1);

namespace App\Application\Media\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\AuctionMedia;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteAuctionImageAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(AuctionMedia $media, User $actor): void
    {
        $media->loadMissing('auction');

        if ($media->auction->seller_id !== $actor->getKey()
            || ! in_array($media->auction->status, [AuctionStatus::Draft, AuctionStatus::PendingApproval], true)
            || ! $actor->hasPermission('auction.update')) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($media, $actor): void {
            $paths = array_values($media->derivatives ?? []);
            $paths[] = $media->original_path;
            $this->auditLogger->record(
                $actor,
                'auction.media_deleted',
                $media,
                ['auction_id' => $media->auction_id, 'path' => $media->original_path],
                null,
                null,
            );
            $wasPrimary = $media->is_primary;
            $auction = $media->auction;
            $disk = $media->disk;
            $media->delete();

            if ($wasPrimary) {
                $auction->media()->first()?->update(['is_primary' => true]);
            }

            DB::afterCommit(fn () => Storage::disk($disk)->delete($paths));
        });
    }
}
