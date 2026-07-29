<?php

declare(strict_types=1);

namespace App\Application\Media\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Jobs\Media\GenerateImageDerivatives;
use App\Models\Auction;
use App\Models\AuctionMedia;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class UploadAuctionImageAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(Auction $auction, User $uploader, UploadedFile $file): AuctionMedia
    {
        if ($auction->seller_id !== $uploader->getKey()
            || ! in_array($auction->status, [AuctionStatus::Draft, AuctionStatus::PendingApproval], true)
            || ! $uploader->hasPermission('auction.update')) {
            throw new AuthorizationException;
        }

        $this->validateFile($auction, $file);
        $bytes = $file->getContent();
        $imageInfo = getimagesizefromstring($bytes);

        if ($imageInfo === false) {
            throw ValidationException::withMessages(['image' => [__('media.invalid_image')]]);
        }

        [$width, $height] = $imageInfo;
        $disk = (string) config('media.disk');
        $extension = match ($imageInfo['mime']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages(['image' => [__('media.unsupported_type')]]),
        };
        $path = "auctions/{$auction->getKey()}/originals/".Str::uuid().".{$extension}";
        Storage::disk($disk)->put($path, $bytes);

        try {
            $media = DB::transaction(function () use ($auction, $uploader, $disk, $path, $imageInfo, $width, $height, $bytes): AuctionMedia {
                $isPrimary = ! $auction->media()->exists();
                $media = $auction->media()->create([
                    'uploaded_by' => $uploader->getKey(),
                    'disk' => $disk,
                    'original_path' => $path,
                    'mime_type' => $imageInfo['mime'],
                    'size_bytes' => strlen($bytes),
                    'width' => $width,
                    'height' => $height,
                    'checksum_sha256' => hash('sha256', $bytes),
                    'sort_order' => ((int) $auction->media()->max('sort_order')) + 1,
                    'is_primary' => $isPrimary,
                ]);
                $this->auditLogger->record(
                    $uploader,
                    'auction.media_uploaded',
                    $media,
                    null,
                    ['auction_id' => $auction->getKey(), 'mime_type' => $imageInfo['mime']],
                    null,
                );

                return $media;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        GenerateImageDerivatives::dispatch($media->getKey())->afterCommit();

        return $media;
    }

    private function validateFile(Auction $auction, UploadedFile $file): void
    {
        $maximumImages = (int) config('media.max_images_per_auction', 12);

        if ($auction->media()->count() >= $maximumImages) {
            throw ValidationException::withMessages(['image' => [__('media.too_many_images')]]);
        }

        if (! $file->isValid() || $file->getSize() > ((int) config('media.max_upload_kilobytes') * 1024)) {
            throw ValidationException::withMessages(['image' => [__('media.file_too_large')]]);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        if (! in_array($mime, config('media.allowed_mime_types'), true)) {
            throw ValidationException::withMessages(['image' => [__('media.unsupported_type')]]);
        }

        $dimensions = getimagesize($file->getRealPath());

        if ($dimensions === false || ($dimensions[0] * $dimensions[1]) > (int) config('media.max_pixels')) {
            throw ValidationException::withMessages(['image' => [__('media.invalid_dimensions')]]);
        }
    }
}
