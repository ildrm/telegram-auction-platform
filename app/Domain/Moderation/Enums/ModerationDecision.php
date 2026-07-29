<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

enum ModerationDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
