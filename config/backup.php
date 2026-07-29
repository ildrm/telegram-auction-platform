<?php

declare(strict_types=1);

return [
    'disk' => env('BACKUP_DISK', 'backups'),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'tables' => [
        'users',
        'categories',
        'roles',
        'permissions',
        'permission_role',
        'role_user',
        'auctions',
        'bids',
        'audit_logs',
        'telegram_updates',
        'conversation_states',
        'telegram_deliveries',
        'auction_moderations',
        'reports',
        'translations',
        'system_settings',
        'watchlists',
        'auction_notifications',
        'reviews',
        'auction_media',
    ],
];
