<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enums;

enum TelegramDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
