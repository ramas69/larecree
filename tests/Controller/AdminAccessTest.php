<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
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
