<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountControllerTest extends WebTestCase
{
    private const EMAIL = 'rama@hallia.ai';

    private function boot(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $c = static::getContainer();
        $em = $c->get(EntityManagerInterface::class);
        $st = new SchemaTool($em);
        $st->dropDatabase();
        $st->createSchema($em->getMetadataFactory()->getAllMetadata());
        $c->get(AppFixtures::class)->load($em);
        $em->clear();

        return $client;
    }

    private function login(KernelBrowser $client): User
    {
        $user = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        $client->loginUser($user);

        return $user;
    }

    public function testAccountPageRenders(): void
    {
        $client = $this->boot();
        $this->login($client);
        $client->request('GET', '/mon-compte');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.dash-hero-title', 'compte');
        self::assertSelectorExists('input[name="email"]');
    }

    public function testAnonymousRedirectedToLogin(): void
    {
        $client = $this->boot();
        $client->request('GET', '/mon-compte');
        self::assertResponseRedirects();
    }

    public function testUpdateProfile(): void
    {
        $client = $this->boot();
        $this->login($client);
        $crawler = $client->request('GET', '/mon-compte');
        $token = $crawler->filter('form[action="/mon-compte/profil"] input[name="_token"]')->attr('value');

        $client->request('POST', '/mon-compte/profil', [
            'first_name' => 'Ramata',
            'last_name'  => 'Soumare',
            'email'      => self::EMAIL,
            '_token'     => $token,
        ]);
        self::assertResponseRedirects('/mon-compte');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        self::assertSame('Ramata', $user->getFirstName());
    }

    public function testChangePassword(): void
    {
        $client = $this->boot();
        $this->login($client);
        $crawler = $client->request('GET', '/mon-compte');
        $token = $crawler->filter('form[action="/mon-compte/mot-de-passe"] input[name="_token"]')->attr('value');

        $client->request('POST', '/mon-compte/mot-de-passe', [
            'current_password'     => 'rama',
            'new_password'         => 'nouveauMDP123',
            'new_password_confirm' => 'nouveauMDP123',
            '_token'               => $token,
        ]);
        self::assertResponseRedirects('/mon-compte');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'nouveauMDP123'));
    }

    public function testChangePasswordWrongCurrentFails(): void
    {
        $client = $this->boot();
        $this->login($client);
        $crawler = $client->request('GET', '/mon-compte');
        $token = $crawler->filter('form[action="/mon-compte/mot-de-passe"] input[name="_token"]')->attr('value');

        $client->request('POST', '/mon-compte/mot-de-passe', [
            'current_password'     => 'mauvais',
            'new_password'         => 'nouveauMDP123',
            'new_password_confirm' => 'nouveauMDP123',
            '_token'               => $token,
        ]);
        self::assertResponseRedirects('/mon-compte');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        // mot de passe inchangé
        self::assertTrue($hasher->isPasswordValid($user, 'rama'));
    }
}
