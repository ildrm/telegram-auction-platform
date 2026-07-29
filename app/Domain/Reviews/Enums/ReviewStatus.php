<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Enums;

enum ReviewStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
}
