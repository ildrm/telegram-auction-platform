<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Enums;

enum AuctionStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';
}
