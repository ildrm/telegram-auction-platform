<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use DomainException;

final class BusinessRuleViolation extends DomainException
{
    public function __construct(
        public readonly string $translationKey,
        string $message,
    ) {
        parent::__construct($message);
    }
}
