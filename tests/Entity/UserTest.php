<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetEnrollmentsReturnsEmptyCollectionOnConstruct(): void
    {
        $user = new User();

        self::assertCount(0, $user->getEnrollments());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getEnrollments());
    }
}
