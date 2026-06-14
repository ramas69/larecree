<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StudentJourneyTest extends WebTestCase
{
    private function bootWithFixtures(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
        $container->get(AppFixtures::class)->load($em);
        $em->clear();

        return $client;
    }

    private function loginAsRama(KernelBrowser $client): User
    {
        $rama = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'rama@hallia.ai']);
        self::assertNotNull($rama);
        $client->loginUser($rama);

        return $rama;
    }

    private function loginAsVip(KernelBrowser $client): User
    {
        $vip = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'vip@larecreetech.com']);
        self::assertNotNull($vip);
        $client->loginUser($vip);

        return $vip;
    }

    public function testCatalogueListsPublishedFormationsWithEnrolledBadge(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        $client->request('GET', '/formations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.cat-hero-title', 'prochaine');
        // 2 formations seedées : Claude (enrollée par Rama) + Manus (coming soon)
        self::assertGreaterThanOrEqual(2, $client->getCrawler()->filter('.cat-card')->count());
        self::assertGreaterThanOrEqual(1, $client->getCrawler()->filter('.cat-card.is-enrolled')->count());
        self::assertGreaterThanOrEqual(1, $client->getCrawler()->filter('.cat-card.is-coming')->count());
        self::assertSelectorTextContains('.cat-card-badge.is-coming', 'Bientôt');
        self::assertSelectorExists('.cat-hero-stats');
    }

    public function testComingSoonFormationShowReturns404(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        $client->request('GET', '/formations/manus-2026');

        self::assertResponseStatusCodeSame(404);
    }

    public function testFormationShowDisplaysProgrammeAndDoneMarkers(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        $client->request('GET', '/formations/claude-2026');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.lesson-title, .dash-hero-title', 'Claude');
        self::assertSelectorExists('.programme-module');
        // M01+M02 toutes leçons done (8 leçons) + M03L1 done = 9 done markers
        self::assertGreaterThanOrEqual(8, $client->getCrawler()->filter('.programme-lesson.is-done')->count());
    }

    public function testLessonShowRequiresEnrollment(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsVip($client);

        // VIP inscrit à Claude, Manus est coming-soon → 404 (gated avant access denied)
        $client->request('GET', '/formations/manus-2026/decouvrir-manus/m1-l1');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLessonShowRendersPlaceholderWhenNoVideo(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        $client->request('GET', '/formations/claude-2026/cerveau-externe/m3-l2');

        self::assertResponseIsSuccessful();
        // Aucune vidéo locale seedée → placeholder, et plus aucun Vimeo
        self::assertSelectorExists('.lp-no-video');
        self::assertStringNotContainsString('vimeo.com', (string) $client->getResponse()->getContent());
    }

    public function testLessonVideoReturns404WhenNoSelfHostedFile(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        // M03L2 n'a pas de vidéo locale → 404 sur la route vidéo
        $client->request('GET', '/formations/claude-2026/cerveau-externe/m3-l2/video');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLessonVideoComingSoonReturns404(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsVip($client);

        $client->request('GET', '/formations/manus-2026/decouvrir-manus/m1-l1/video');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLessonVideoStreamsForEnrolled(): void
    {
        $client = $this->bootWithFixtures();
        $this->loginAsRama($client);

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $projectDir = $container->getParameter('kernel.project_dir');

        // Trouver M03L2 et lui attacher une vidéo locale + écrire le fichier
        $lesson = null;
        $formation = $em->getRepository(Formation::class)->findOneBy(['slug' => 'claude-2026']);
        foreach ($formation->getModules() as $m) {
            if ($m->getSlug() === 'cerveau-externe') {
                foreach ($m->getLessons() as $l) {
                    if ($l->getSlug() === 'm3-l2') {
                        $lesson = $l;
                        break 2;
                    }
                }
            }
        }
        self::assertNotNull($lesson);

        $videoName = 'test-stream.mp4';
        $lesson->setVideoFilename($videoName);
        $em->flush();

        $dir = $projectDir.'/private/videos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filePath = $dir.'/'.$videoName;
        file_put_contents($filePath, 'FAKEMP4BYTES');

        try {
            $client->request('GET', '/formations/claude-2026/cerveau-externe/m3-l2/video');

            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Type', 'video/mp4');
        } finally {
            @unlink($filePath);
        }
    }

    public function testMarkCompletedCreatesProgressAndRedirects(): void
    {
        $client = $this->bootWithFixtures();
        $rama = $this->loginAsRama($client);

        // Choisir leçon non encore vue : M04L1 (Claude)
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $formation = $em->getRepository(Formation::class)->findOneBy(['slug' => 'claude-2026']);
        self::assertNotNull($formation);

        $client->request('GET', '/formations/claude-2026/produire-livrables/m4-l1');
        $token = $client->getCrawler()->filter('input[name="_token"]')->attr('value');
        self::assertNotEmpty($token);

        $client->request('POST', '/formations/claude-2026/produire-livrables/m4-l1/complete', [
            '_token' => $token,
        ]);
        // Doit enchaîner sur la leçon suivante du module
        self::assertResponseRedirects('/formations/claude-2026/produire-livrables/m4-l2');

        // Vérif progress créé + completedAt non-null
        $em->clear();
        $reloadedUser = $em->getRepository(User::class)->find($rama->getId());
        $reloadedFormation = $em->getRepository(Formation::class)->find($formation->getId());
        $enrollment = $em->getRepository(Enrollment::class)
            ->findOneBy(['user' => $reloadedUser, 'formation' => $reloadedFormation]);
        self::assertNotNull($enrollment);

        $lesson = null;
        foreach ($enrollment->getFormation()->getModules() as $m) {
            if ($m->getSlug() === 'produire-livrables') {
                foreach ($m->getLessons() as $l) {
                    if ($l->getSlug() === 'm4-l1') {
                        $lesson = $l;
                        break 2;
                    }
                }
            }
        }
        self::assertNotNull($lesson);

        $progress = $em->getRepository(LessonProgress::class)
            ->findOneBy(['enrollment' => $enrollment, 'lesson' => $lesson]);
        self::assertNotNull($progress);
        self::assertTrue($progress->isCompleted());
        self::assertSame(100, $progress->getPercentWatched());
    }
}
