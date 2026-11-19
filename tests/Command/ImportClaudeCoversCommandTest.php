<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\DataFixtures\AppFixtures;
use App\Entity\Lesson;
use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportClaudeCoversCommandTest extends KernelTestCase
{
    public function testCoversOfStepsAndLessonsAreImported(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
        $container->get(AppFixtures::class)->load($em);
        $em->clear();

        $uploads = $container->getParameter('kernel.project_dir').'/public/uploads';
        try {
            $tester = new CommandTester((new Application($kernel))->find('app:import-claude-covers'));
            self::assertSame(0, $tester->execute([]));

            $em->clear();
            $lesson = $em->getRepository(Lesson::class)->findOneBy(['slug' => 'm1-l1']);
            $module = $em->getRepository(Module::class)->findOneBy(['slug' => 'etape-1-cowork']);
            self::assertSame('claude-2026-m1-l1.jpg', $lesson?->getCoverImage());
            self::assertSame('claude-2026-etape-1-cowork.jpg', $module?->getCoverImage());
            self::assertFileExists($uploads.'/lessons/claude-2026-m1-l1.jpg');
            self::assertFileExists($uploads.'/modules/claude-2026-etape-1-cowork.jpg');
        } finally {
            array_map('unlink', [...glob($uploads.'/lessons/claude-2026-*.jpg') ?: [], ...glob($uploads.'/modules/claude-2026-*.jpg') ?: []]);
        }
    }
}
