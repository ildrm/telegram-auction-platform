<?php

declare(strict_types=1);

return [
    'welcome' => 'Welcome, :name. Use /auctions to browse or /sell to create an auction.',
    'unknown_command' => 'I do not recognize that command. Use /help.',
    'text_only' => 'Please send text for this step.',
    'cancelled' => 'The current operation was cancelled.',
    'invalid_action' => 'That action is no longer available.',
    'no_auctions' => 'There are no active public auctions right now.',
    'no_categories' => 'Auction creation is unavailable because no categories are active.',
    'auction_line' => '<b>:title</b>'.PHP_EOL.':price :currency'.PHP_EOL.'Reference: <code>:slug</code>',
    'buttons' => [
        'auctions' => 'Browse auctions',
    ],
    'wizard' => [
        'category' => 'Send the category number:'.PHP_EOL.PHP_EOL.':categories',
        'invalid_category' => 'Choose one of the listed active category numbers.',
        'title' => 'Send the auction title (3–160 characters).',
        'invalid_title' => 'The title must contain 3–160 characters.',
        'description' => 'Send a detailed description (at least 10 characters).',
        'invalid_description' => 'The description must contain 10–20,000 characters.',
        'starting_price' => 'Send the starting price in minor units (for example, 1250 means 12.50).',
        'minimum_increment' => 'Send the minimum bid increment in minor units.',
        'invalid_amount' => 'Send a positive whole number.',
        'duration' => 'Send the auction duration in hours (1–720).',
        'invalid_duration' => 'Duration must be a whole number from 1 to 720 hours.',
        'created' => 'Draft created: <b>:title</b>'.PHP_EOL.'Reference: <code>:slug</code>',
        'expired' => 'That conversation expired. Start again with /sell.',
    ],
];
