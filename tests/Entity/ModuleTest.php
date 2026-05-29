<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Module;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $module = new Module();

        self::assertNull($module->getId());
        self::assertNull($module->getFormation());
        self::assertSame(0, $module->getDisplayOrder());
        self::assertInstanceOf(\DateTimeImmutable::class, $module->getCreatedAt());
        self::assertNull($module->getUpdatedAt());
    }

    public function testFormationIsAssignedThroughFormationAddModule(): void
    {
        $formation = new Formation();
        $module = (new Module())
            ->setTitle('Démarrer avec Claude')
            ->setSlug('demarrer-claude')
            ->setDisplayOrder(1);

        $formation->addModule($module);

        self::assertSame($formation, $module->getFormation());
        self::assertCount(1, $formation->getModules());
    }

    public function testRemoveModuleDetachesIt(): void
    {
        $formation = new Formation();
        $module = (new Module())->setTitle('A')->setSlug('a');
        $formation->addModule($module);

        $formation->removeModule($module);

        self::assertNull($module->getFormation());
        self::assertCount(0, $formation->getModules());
    }
}
