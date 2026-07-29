<?php

declare(strict_types=1);

namespace App\Application\Auctions\Services;

use App\Application\Settings\SettingService;
use App\Models\Auction;

final readonly class AntiSnipingService
{
    public function __construct(private SettingService $settings) {}

    public function extendWhenNecessary(Auction $auction): bool
    {
        if (! $auction->anti_sniping_enabled || $auction->extension_count >= $auction->max_extensions) {
            return false;
        }

        $window = (int) $this->settings->get('auction.anti_sniping_window_seconds', 30);

        if (now()->diffInSeconds($auction->ends_at, false) > $window) {
            return false;
        }

        $extension = (int) $this->settings->get('auction.anti_sniping_extension_seconds', 120);
        $auction->ends_at = $auction->ends_at->addSeconds($extension);
        $auction->extension_count++;

        return true;
    }
}
