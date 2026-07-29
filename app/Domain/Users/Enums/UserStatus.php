<?php

declare(strict_types=1);

namespace App\Domain\Users\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case Inactive = 'inactive';
}
