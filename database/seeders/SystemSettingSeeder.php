<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

final class SystemSettingSeeder extends Seeder
{
    /** @var array<string, array{value: mixed, type: string, is_public: bool}> */
    private const SETTINGS = [
        'auction.approval_required' => ['value' => true, 'type' => 'boolean', 'is_public' => true],
        'auction.anti_sniping_window_seconds' => ['value' => 30, 'type' => 'integer', 'is_public' => true],
        'auction.anti_sniping_extension_seconds' => ['value' => 120, 'type' => 'integer', 'is_public' => true],
        'auction.review_window_days' => ['value' => 30, 'type' => 'integer', 'is_public' => true],
        'media.max_upload_kilobytes' => ['value' => 10_240, 'type' => 'integer', 'is_public' => true],
        'media.max_images_per_auction' => ['value' => 12, 'type' => 'integer', 'is_public' => true],
        'platform.default_currency' => ['value' => 'USD', 'type' => 'string', 'is_public' => true],
        'platform.default_locale' => ['value' => 'en', 'type' => 'string', 'is_public' => true],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => $attributes) {
            SystemSetting::query()->updateOrCreate(['key' => $key], $attributes);
        }
    }
}
