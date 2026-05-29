<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class EnrollmentTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $enrollment = new Enrollment();

        self::assertNull($enrollment->getId());
        self::assertNull($enrollment->getUser());
        self::assertNull($enrollment->getFormation());
        self::assertNull($enrollment->getSource());
        self::assertNull($enrollment->getStripeSessionId());
        self::assertNull($enrollment->getStripePaymentIntentId());
        self::assertNull($enrollment->getAmountCents());
        self::assertInstanceOf(\DateTimeImmutable::class, $enrollment->getCreatedAt());
    }

    public function testAddingEnrollmentToUserBindsBothSides(): void
    {
        $user = new User();
        $enrollment = (new Enrollment())->setSource(EnrollmentSource::Stripe);

        $user->addEnrollment($enrollment);

        self::assertSame($user, $enrollment->getUser());
        self::assertCount(1, $user->getEnrollments());
    }

    public function testAddingEnrollmentToFormationBindsBothSides(): void
    {
        $formation = new Formation();
        $enrollment = (new Enrollment())->setSource(EnrollmentSource::Stripe);

        $formation->addEnrollment($enrollment);

        self::assertSame($formation, $enrollment->getFormation());
        self::assertCount(1, $formation->getEnrollments());
    }

    public function testIsPaidIsTrueOnlyForStripeSource(): void
    {
        $stripe = (new Enrollment())->setSource(EnrollmentSource::Stripe);
        $vip    = (new Enrollment())->setSource(EnrollmentSource::Vip);
        $admin  = (new Enrollment())->setSource(EnrollmentSource::Admin);

        self::assertTrue($stripe->isPaid());
        self::assertFalse($vip->isPaid());
        self::assertFalse($admin->isPaid());
    }

    public function testIsVipGrantedIsTrueOnlyForVipSource(): void
    {
        $vip    = (new Enrollment())->setSource(EnrollmentSource::Vip);
        $stripe = (new Enrollment())->setSource(EnrollmentSource::Stripe);
        $admin  = (new Enrollment())->setSource(EnrollmentSource::Admin);

        self::assertTrue($vip->isVipGranted());
        self::assertFalse($stripe->isVipGranted());
        self::assertFalse($admin->isVipGranted());
    }

    public function testIsPaidAndIsVipGrantedAreFalseWhenSourceIsNull(): void
    {
        $enrollment = new Enrollment();

        self::assertFalse($enrollment->isPaid());
        self::assertFalse($enrollment->isVipGranted());
    }

    public function testAmountCentsRoundTrips(): void
    {
        $enrollment = (new Enrollment())->setAmountCents(39700);

        self::assertSame(39700, $enrollment->getAmountCents());
    }
}
