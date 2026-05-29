<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\EnrollmentSource;
use PHPUnit\Framework\TestCase;

final class EnrollmentSourceTest extends TestCase
{
    public function testEnumHasStripeVipAdminCases(): void
    {
        self::assertSame('stripe', EnrollmentSource::Stripe->value);
        self::assertSame('vip', EnrollmentSource::Vip->value);
        self::assertSame('admin', EnrollmentSource::Admin->value);
    }

    public function testFromStringResolvesEnumCase(): void
    {
        self::assertSame(EnrollmentSource::Stripe, EnrollmentSource::from('stripe'));
        self::assertSame(EnrollmentSource::Vip, EnrollmentSource::from('vip'));
        self::assertSame(EnrollmentSource::Admin, EnrollmentSource::from('admin'));
    }

    public function testCasesReturnsExactlyThreeCases(): void
    {
        self::assertCount(3, EnrollmentSource::cases());
    }
}
