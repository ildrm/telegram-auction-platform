<?php

declare(strict_types=1);

return [
    'disk' => env('AUCTION_MEDIA_DISK', 'auction-media'),
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_upload_kilobytes' => (int) env('AUCTION_MEDIA_MAX_KB', 10_240),
    'max_images_per_auction' => (int) env('AUCTION_MEDIA_MAX_IMAGES', 12),
    'max_pixels' => (int) env('AUCTION_MEDIA_MAX_PIXELS', 40_000_000),
    'derivatives' => [
        'thumbnail' => 320,
        'display' => 1280,
    ],
];
