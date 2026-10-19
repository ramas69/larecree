<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

/** Les formulaires de larecreetech.com : la candidature à la cohorte et le quiz. */
final class SiteControllerTest extends WebTestCase
{
    private const FORMULAIRE = [
        'prenom' => 'Awa', 'nom' => 'Diop', 'email' => 'awa@example.com', 'telephone' => '', 'situation' => 'freelance', 'link' => '',
        'claude_level' => 'tested', 'project_idea' => 'clear', 'project_text' => "Une app de réservation.\nPour mon salon.",
        'availability' => 'usually', 'source' => 'linkedin', 'rgpd' => 'on', 'cohorte' => '3', '_gotcha' => '', 'message' => 'ignoré',
    ];

    private function client(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        return $client;
    }

    private function nombre(string $table = 'candidature'): int
    {
        return (int) static::getContainer()->get(Connection::class)->fetchOne("SELECT COUNT(*) FROM $table");
    }

    public function testQuizGardeUneFoisParAdresse(): void
    {
        $client = $this->client();
        $quiz = ['email' => 'Lea@Example.com', 'profil' => 'Le Pilote', 'consentement' => '1', '_gotcha' => ''];
        $client->request('POST', '/quiz', $quiz, [], ['HTTP_ORIGIN' => 'https://larecreetech.com']);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'https://larecreetech.com');
        self::assertEmailCount(1);
        self::assertSame('🌸 Quiz — Le Pilote', self::getMailerMessage()->getSubject());

        // elle refait le quiz (autre profil, même adresse) : elle a son plan, mais rien de nouveau n'est gardé ni envoyé
        $client->request('POST', '/quiz', ['email' => 'lea@example.com', 'profil' => 'Le Prudent'] + $quiz);
        self::assertResponseStatusCodeSame(201);
        self::assertEmailCount(0);
        self::assertSame(['lea@example.com', 'Le Pilote'], array_values(static::getContainer()->get(Connection::class)->fetchAssociative('SELECT email, profil FROM contact_quiz')));
    }

    public function testQuizSansAccordRefuse(): void
    {
        $client = $this->client();
        $client->request('POST', '/quiz', ['email' => 'lea@example.com', 'profil' => 'Le Pilote']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->nombre('contact_quiz'));
    }

    public function testCandidatureGardeeEtEnvoyeeAHello(): void
    {
        $client = $this->client();
        $client->request('POST', '/candidature', self::FORMULAIRE, [], ['HTTP_ORIGIN' => 'https://larecreetech.com']);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'https://larecreetech.com');
        $ligne = static::getContainer()->get(Connection::class)->fetchAssociative('SELECT * FROM candidature');
        self::assertSame(['Awa', 'Freelance', 'Quelques tests', "Une app de réservation.\nPour mon salon.", 3, null],
            [$ligne['prenom'], $ligne['situation'], $ligne['niveau_claude'], $ligne['projet'], (int) $ligne['cohorte'], $ligne['telephone']]);

        self::assertEmailCount(1);
        /** @var Email $mail */
        $mail = self::getMailerMessage();
        self::assertSame('hello@larecreetech.com', $mail->getTo()[0]->getAddress());
        self::assertSame('awa@example.com', $mail->getReplyTo()[0]->getAddress());
        self::assertSame('Candidature cohorte 03 — Awa Diop', $mail->getSubject());
        self::assertStringContainsString('Profil : Freelance', (string) $mail->getTextBody());
    }

    public function testFormulaireIncompletRefuse(): void
    {
        $client = $this->client();
        $client->request('POST', '/candidature', ['email' => 'pas-un-mail', 'situation' => 'pirate', 'prenom' => ['tableau']] + self::FORMULAIRE, [], ['HTTP_ORIGIN' => 'https://ailleurs.example']);

        self::assertResponseStatusCodeSame(422);
        self::assertResponseNotHasHeader('Access-Control-Allow-Origin');
        $erreurs = json_decode((string) $client->getResponse()->getContent(), true)['erreurs'];
        self::assertSame(['prenom', 'email', 'situation'], array_keys($erreurs));
        self::assertSame(0, $this->nombre());
        self::assertEmailCount(0);
    }

    public function testRobotIgnore(): void
    {
        $client = $this->client();
        $client->request('POST', '/candidature', ['_gotcha' => 'spam'] + self::FORMULAIRE);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(0, $this->nombre());
        self::assertEmailCount(0);
    }
}
