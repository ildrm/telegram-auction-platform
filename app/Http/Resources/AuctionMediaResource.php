<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin \App\Models\AuctionMedia */
final class AuctionMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = ['original' => $this->original_path];

        foreach ($this->derivatives ?? [] as $name => $path) {
            $variants[$name] = $path;
        }

        return [
            'id' => $this->getKey(),
            'width' => $this->width,
            'height' => $this->height,
            'is_primary' => $this->is_primary,
            'processing_status' => $this->processing_status,
            'urls' => collect($variants)->map(
                fn (string $path, string $variant): string => URL::temporarySignedRoute(
                    'auction-media.show',
                    now()->addHour(),
                    ['media' => $this->getKey(), 'variant' => $variant],
                ),
            )->all(),
        ];
    }
}
