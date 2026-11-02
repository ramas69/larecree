<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    public function testAuthenticatedDashboardRendersFixturesData(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        /** @var AppFixtures $fixtures */
        $fixtures = $container->get(AppFixtures::class);
        $fixtures->load($em);
        $em->clear();

        /** @var User $rama */
        $rama = $em->getRepository(User::class)->findOneBy(['email' => 'rama@hallia.ai']);
        self::assertNotNull($rama);
        $client->loginUser($rama);

        $client->request('GET', '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.cb-titre', 'Bon');
        self::assertSelectorTextContains('.cb-surtitre', 'Reprendre');
        // currentProgress = la plus récente non terminée (Design M02 L01 ou Claude M03 L02)
        self::assertSelectorExists('.cb-ou');
        // Au moins une carte de parcours (Rama inscrit à Claude)
        self::assertGreaterThanOrEqual(1, $client->getCrawler()->filter('.cb-edt')->count());
        // progress map
        self::assertSelectorExists('.cb-case.est-fait');
        self::assertSelectorExists('.cb-case.est-en-cours');
        // Même progression que le catalogue : leçons terminées / leçons de la formation (14 sur 30 dans les fixtures).
        self::assertSelectorTextContains('.cb-edt-tete', '47 %');
    }
}
