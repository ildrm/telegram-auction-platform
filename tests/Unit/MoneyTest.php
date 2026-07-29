<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_it_adds_money_in_the_same_currency(): void
    {
        $result = (new Money(minor: 1_000, currency: 'USD'))
            ->add(new Money(minor: 250, currency: 'USD'));

        self::assertSame(1_250, $result->minor);
        self::assertSame('USD', $result->currency);
    }

    public function test_it_rejects_mixed_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(minor: 1_000, currency: 'USD'))
            ->add(new Money(minor: 250, currency: 'EUR'));
    }
}
