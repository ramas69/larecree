<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LessonRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LessonRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(LessonRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByModuleOrderedReturnsLessonsSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $module = (new Module())->setTitle('Démarrer')->setSlug('demarrer')->setDisplayOrder(1);
        $formation->addModule($module);

        $second = (new Lesson())->setTitle('Second')->setSlug('second')->setVimeoVideoId('222')->setDisplayOrder(2);
        $first  = (new Lesson())->setTitle('First') ->setSlug('first') ->setVimeoVideoId('111')->setDisplayOrder(1);
        $third  = (new Lesson())->setTitle('Third') ->setSlug('third') ->setVimeoVideoId('333')->setDisplayOrder(3);

        $module->addLesson($second);
        $module->addLesson($first);
        $module->addLesson($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByModuleOrdered($module);

        self::assertCount(3, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
        self::assertSame('third', $result[2]->getSlug());
    }

    public function testFindByModuleOrderedReturnsEmptyArrayWhenModuleHasNoLessons(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $module = (new Module())->setTitle('Empty Module')->setSlug('empty');
        $formation->addModule($module);

        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByModuleOrdered($module));
    }
}
