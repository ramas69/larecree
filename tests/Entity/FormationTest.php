<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Formation;
use PHPUnit\Framework\TestCase;

final class FormationTest extends TestCase
{
    public function testConstructorSetsRequiredDefaults(): void
    {
        $formation = new Formation();

        self::assertNull($formation->getId());
        self::assertFalse($formation->isPublished());
        self::assertSame(0, $formation->getDisplayOrder());
        self::assertSame('EUR', $formation->getCurrency());
        self::assertInstanceOf(\DateTimeImmutable::class, $formation->getCreatedAt());
        self::assertNull($formation->getUpdatedAt());
    }

    public function testTitleAndSubtitleSetters(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Claude 2026');
        $formation->setSubtitle('La formation complète');

        self::assertSame('Formation Claude 2026', $formation->getTitle());
        self::assertSame('La formation complète', $formation->getSubtitle());
    }

    public function testGetPriceFormattedRendersEuroFromCents(): void
    {
        $formation = new Formation();
        $formation->setPriceCents(39700);

        self::assertSame('397,00 €', $formation->getPriceFormatted());
    }

    public function testGetPriceFormattedHandlesZero(): void
    {
        $formation = new Formation();

        self::assertSame('0,00 €', $formation->getPriceFormatted());
    }

    public function testGetModulesReturnsEmptyCollectionOnConstruct(): void
    {
        $formation = new Formation();

        self::assertCount(0, $formation->getModules());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $formation->getModules());
    }
}
