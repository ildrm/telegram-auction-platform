<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enums;

enum TelegramUpdateStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
}
