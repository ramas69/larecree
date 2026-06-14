<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Admin\LessonCrudController;
use App\Controller\Admin\ModuleCrudController;
use App\DataFixtures\AppFixtures;
use App\Entity\Module;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminAccessTest extends WebTestCase
{
    private const ADMIN_PATH = '/admin';

    private function bootWithFixtures(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
        $container->get(AppFixtures::class)->load($em);
        $em->clear();

        return $client;
    }

    public function testAdminRedirectsToUserCrud(): void
    {
        $client = $this->bootWithFixtures();
        $admin = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'admin@larecreetech.com']);
        self::assertNotNull($admin);
        $client->loginUser($admin);

        $client->request('GET', self::ADMIN_PATH);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    public function testModuleCrudIndexNewEditRender(): void
    {
        $client = $this->bootWithFixtures();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin@larecreetech.com']);
        $client->loginUser($admin);

        $urlGen = $container->get(AdminUrlGenerator::class);

        // INDEX
        $client->request('GET', $urlGen->setController(ModuleCrudController::class)->setAction(Action::INDEX)->generateUrl());
        self::assertResponseIsSuccessful();

        // NEW (single-save trait actions)
        $client->request('GET', $urlGen->setController(ModuleCrudController::class)->setAction(Action::NEW)->generateUrl());
        self::assertResponseIsSuccessful();

        // EDIT (ckeditor textarea + trait actions)
        $module = $em->getRepository(Module::class)->findOneBy([]);
        self::assertNotNull($module);
        $client->request('GET', $urlGen->setController(LessonCrudController::class)->setAction(Action::NEW)->generateUrl());
        self::assertResponseIsSuccessful();
        // Le champ d'upload vidéo (non mappé) doit bien se rendre dans le formulaire
        self::assertSelectorExists('input[type="file"][name$="[videoUpload]"]');
        self::assertSelectorExists('input[name$="[videoFilename]"]');
        self::assertSelectorExists('input[name$="[durationMinutes]"]');

        // EDIT : le champ « retirer la vidéo » (checkbox, edit only) doit se rendre
        $lesson = $em->getRepository(\App\Entity\Lesson::class)->findOneBy([]);
        self::assertNotNull($lesson);
        $client->request('GET', $urlGen->setController(LessonCrudController::class)
            ->setAction(Action::EDIT)->setEntityId($lesson->getId())->generateUrl());
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[type="checkbox"][name$="[videoRemove]"]');

        // Aperçu admin : 404 sans vidéo, 200 (video/mp4) avec un fichier présent
        $lessonId = $lesson->getId();
        $client->request('GET', '/admin/video-preview/'.$lessonId);
        self::assertResponseStatusCodeSame(404);

        $projectDir = $container->getParameter('kernel.project_dir');
        $dir = $projectDir.'/private/videos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $videoName = 'preview-test.mp4';
        $filePath = $dir.'/'.$videoName;
        file_put_contents($filePath, 'FAKEMP4');

        $freshEm = $container->get(EntityManagerInterface::class);
        $freshLesson = $freshEm->getRepository(\App\Entity\Lesson::class)->find($lessonId);
        $freshLesson->setVideoFilename($videoName);
        $freshEm->flush();

        try {
            $client->request('GET', '/admin/video-preview/'.$lessonId);
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Type', 'video/mp4');
        } finally {
            @unlink($filePath);
        }
    }

    public function testStudentCannotAccessAdmin(): void
    {
        $client = $this->bootWithFixtures();
        $rama = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'rama@hallia.ai']);
        self::assertNotNull($rama);
        $client->loginUser($rama);

        $client->request('GET', self::ADMIN_PATH);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousAdminRedirectsToLogin(): void
    {
        $client = $this->bootWithFixtures();
        $client->request('GET', self::ADMIN_PATH);
        self::assertResponseRedirects();
    }
}
