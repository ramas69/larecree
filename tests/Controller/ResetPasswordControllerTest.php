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

final class ResetPasswordControllerTest extends WebTestCase
{
    private const URL_REQUEST = '/reset-password';
    private const RAMA_EMAIL  = 'rama@hallia.ai';
    private const TOKEN_INPUT = 'input[name="_token"]';

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

    public function testRequestFormRenders(): void
    {
        $client = $this->bootWithFixtures();
        $client->request('GET', self::URL_REQUEST);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.auth-title', 'arrive');
        self::assertSelectorExists('input[name="email"]');
    }

    public function testRequestUnknownEmailStillShowsConfirmation(): void
    {
        $client = $this->bootWithFixtures();
        $crawler = $client->request('GET', self::URL_REQUEST);
        $token = $crawler->filter(self::TOKEN_INPUT)->attr('value');
        self::assertNotEmpty($token);

        $client->request('POST', self::URL_REQUEST, [
            'email'  => 'inconnu@example.com',
            '_token' => $token,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.auth-card', 'Si un compte existe');
    }

    public function testRequestKnownEmailPersistsToken(): void
    {
        $client = $this->bootWithFixtures();
        $crawler = $client->request('GET', self::URL_REQUEST);
        $token = $crawler->filter(self::TOKEN_INPUT)->attr('value');

        $client->request('POST', self::URL_REQUEST, [
            'email'  => self::RAMA_EMAIL,
            '_token' => $token,
        ]);

        self::assertResponseIsSuccessful();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::RAMA_EMAIL]);
        self::assertNotNull($user->getResetPasswordToken(), 'Reset token must be persisted after request.');
        self::assertNotNull($user->getResetPasswordExpiresAt());
        self::assertTrue($user->getResetPasswordExpiresAt() > new \DateTimeImmutable());
    }

    public function testConfirmInvalidTokenRenders410(): void
    {
        $client = $this->bootWithFixtures();
        $client->request('GET', self::URL_REQUEST.'/inexistant-token-xyz');

        self::assertResponseStatusCodeSame(410);
        self::assertSelectorTextContains('.auth-title', 'plus valide');
    }

    public function testConfirmValidTokenChangesPassword(): void
    {
        $client = $this->bootWithFixtures();

        $crawler = $client->request('GET', self::URL_REQUEST);
        $reqToken = $crawler->filter(self::TOKEN_INPUT)->attr('value');
        $client->request('POST', self::URL_REQUEST, [
            'email'  => self::RAMA_EMAIL,
            '_token' => $reqToken,
        ]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::RAMA_EMAIL]);
        $resetToken = $user->getResetPasswordToken();
        self::assertNotNull($resetToken);

        $confirmCrawler = $client->request('GET', self::URL_REQUEST.'/'.$resetToken);
        $csrf = $confirmCrawler->filter(self::TOKEN_INPUT)->attr('value');

        $client->request('POST', self::URL_REQUEST.'/'.$resetToken, [
            'password'         => 'nouveauMDP1234',
            'password_confirm' => 'nouveauMDP1234',
            '_token'           => $csrf,
        ]);

        self::assertResponseRedirects('/login');

        $em->clear();
        $reloaded = $em->getRepository(User::class)->findOneBy(['email' => self::RAMA_EMAIL]);
        self::assertNull($reloaded->getResetPasswordToken());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($reloaded, 'nouveauMDP1234'));
    }
}
