<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Auctions\Enums\AuctionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:20_000'],
            'type' => ['required', Rule::enum(AuctionType::class)],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
            'starting_price_minor' => ['required', 'integer', 'min:0'],
            'minimum_increment_minor' => ['required', 'integer', 'min:1'],
            'reserve_price_minor' => ['nullable', 'integer', 'min:1'],
            'buy_now_price_minor' => ['nullable', 'integer', 'gte:starting_price_minor', 'required_if:type,buy_now,hybrid'],
            'price_decrement_minor' => ['nullable', 'integer', 'min:1', 'lt:starting_price_minor', 'required_if:type,dutch'],
            'price_decrement_interval_seconds' => ['nullable', 'integer', 'min:10', 'max:86400', 'required_if:type,dutch'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_private' => ['required', 'boolean'],
            'anti_sniping_enabled' => ['sometimes', 'boolean'],
            'max_extensions' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
