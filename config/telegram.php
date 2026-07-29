<?php

declare(strict_types=1);

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'conversation_ttl_minutes' => (int) env('TELEGRAM_CONVERSATION_TTL_MINUTES', 30),
    'delivery_tries' => (int) env('TELEGRAM_DELIVERY_TRIES', 5),
    'delivery_timeout_seconds' => (int) env('TELEGRAM_DELIVERY_TIMEOUT_SECONDS', 10),
    'page_size' => (int) env('TELEGRAM_PAGE_SIZE', 8),
];
