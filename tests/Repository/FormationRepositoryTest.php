<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FormationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormationRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(FormationRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindPublishedReturnsOnlyPublishedOrderedByDisplayOrder(): void
    {
        $draft = (new Formation())->setSlug('draft')->setTitle('Draft')->setPriceCents(0)->setDisplayOrder(1);
        $second = (new Formation())->setSlug('second')->setTitle('Second')->setPriceCents(10000)->setPublished(true)->setDisplayOrder(2);
        $first = (new Formation())->setSlug('first')->setTitle('First')->setPriceCents(10000)->setPublished(true)->setDisplayOrder(1);

        $this->em->persist($draft);
        $this->em->persist($second);
        $this->em->persist($first);
        $this->em->flush();

        $result = $this->repo->findPublished();

        self::assertCount(2, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
    }

    public function testFindBySlugReturnsFormationWhenItExists(): void
    {
        $formation = (new Formation())
            ->setSlug('formation-claude')
            ->setTitle('Formation Claude')
            ->setPriceCents(39700)
            ->setPublished(true);

        $this->em->persist($formation);
        $this->em->flush();

        $loaded = $this->repo->findBySlug('formation-claude');

        self::assertNotNull($loaded);
        self::assertSame('Formation Claude', $loaded->getTitle());
    }

    public function testFindBySlugReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->repo->findBySlug('does-not-exist'));
    }
}
