<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bid */
final class BidResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'auction_id' => $this->auction_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at->toIso8601String(),
        ];
    }
}
