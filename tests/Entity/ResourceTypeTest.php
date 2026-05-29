<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ResourceType;
use PHPUnit\Framework\TestCase;

final class ResourceTypeTest extends TestCase
{
    public function testEnumHasLinkAndFileCases(): void
    {
        self::assertSame('link', ResourceType::Link->value);
        self::assertSame('file', ResourceType::File->value);
    }

    public function testFromStringResolvesEnumCase(): void
    {
        self::assertSame(ResourceType::Link, ResourceType::from('link'));
        self::assertSame(ResourceType::File, ResourceType::from('file'));
    }

    public function testCasesReturnsExactlyTwoCases(): void
    {
        self::assertCount(2, ResourceType::cases());
    }
}
