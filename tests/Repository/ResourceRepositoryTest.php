<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Resource;
use App\Entity\ResourceType;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ResourceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ResourceRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(ResourceRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByLessonOrderedReturnsResourcesSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $module    = (new Module())->setTitle('Démarrer')->setSlug('demarrer');
        $lesson    = (new Lesson())->setTitle('Intro')->setSlug('intro');

        $formation->addModule($module);
        $module->addLesson($lesson);

        $second = (new Resource())->setType(ResourceType::Link)->setTitle('Doc')->setUrl('https://docs.example.com')->setDisplayOrder(2);
        $first  = (new Resource())->setType(ResourceType::File)->setTitle('PDF')->setFilePath('/uploads/a.pdf')->setDisplayOrder(1);
        $third  = (new Resource())->setType(ResourceType::Link)->setTitle('Notion')->setUrl('https://notion.so/x')->setDisplayOrder(3);

        $lesson->addResource($second);
        $lesson->addResource($first);
        $lesson->addResource($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByLessonOrdered($lesson);

        self::assertCount(3, $result);
        self::assertSame('PDF', $result[0]->getTitle());
        self::assertSame('Doc', $result[1]->getTitle());
        self::assertSame('Notion', $result[2]->getTitle());
    }

    public function testFindByLessonOrderedReturnsEmptyArrayWhenLessonHasNoResources(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $module    = (new Module())->setTitle('Empty Module')->setSlug('empty');
        $lesson    = (new Lesson())->setTitle('Empty Lesson')->setSlug('empty');

        $formation->addModule($module);
        $module->addLesson($lesson);

        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByLessonOrdered($lesson));
    }
}
