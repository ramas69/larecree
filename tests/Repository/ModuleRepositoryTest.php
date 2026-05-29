<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Module;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ModuleRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ModuleRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(ModuleRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByFormationOrderedReturnsModulesSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $second = (new Module())->setTitle('Second')->setSlug('second')->setDisplayOrder(2);
        $first = (new Module())->setTitle('First')->setSlug('first')->setDisplayOrder(1);
        $third = (new Module())->setTitle('Third')->setSlug('third')->setDisplayOrder(3);

        $formation->addModule($second);
        $formation->addModule($first);
        $formation->addModule($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByFormationOrdered($formation);

        self::assertCount(3, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
        self::assertSame('third', $result[2]->getSlug());
    }

    public function testFindByFormationOrderedReturnsEmptyArrayWhenFormationHasNoModules(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByFormationOrdered($formation));
    }
}
