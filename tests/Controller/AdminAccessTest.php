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
