<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Models\AuctionMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class GenerateImageDerivatives implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $mediaId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $media = AuctionMedia::query()->findOrFail($this->mediaId);
        $disk = Storage::disk($media->disk);
        $source = imagecreatefromstring($disk->get($media->original_path));

        if ($source === false) {
            throw new RuntimeException('The stored original image cannot be decoded.');
        }

        $derivatives = [];

        try {
            foreach ((array) config('media.derivatives') as $name => $maxWidth) {
                $width = min((int) $maxWidth, $media->width);
                $height = max(1, (int) round($media->height * ($width / $media->width)));
                $target = imagecreatetruecolor($width, $height);

                if ($target === false) {
                    throw new RuntimeException('Unable to allocate image derivative.');
                }

                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $media->width, $media->height);
                ob_start();
                imagewebp($target, null, 82);
                $contents = ob_get_clean();
                imagedestroy($target);

                if (! is_string($contents)) {
                    throw new RuntimeException('Unable to encode image derivative.');
                }

                $path = "auctions/{$media->auction_id}/derivatives/{$media->getKey()}-{$name}.webp";
                $disk->put($path, $contents);
                $derivatives[(string) $name] = $path;
            }
        } finally {
            imagedestroy($source);
        }

        $media->update([
            'derivatives' => $derivatives,
            'processing_status' => 'ready',
            'processing_error' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        AuctionMedia::query()->whereKey($this->mediaId)->update([
            'processing_status' => 'failed',
            'processing_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
