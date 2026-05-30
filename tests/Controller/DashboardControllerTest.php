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
        self::assertSelectorTextContains('.dash-hero-title', 'Bon');
        self::assertSelectorTextContains('.dash-section-title', 'Reprendre');
        // currentProgress = la plus récente non terminée (Design M02 L01 ou Claude M03 L02)
        self::assertSelectorExists('.dash-continue-eyebrow');
        // Au moins une carte de parcours par formation
        self::assertGreaterThanOrEqual(2, $client->getCrawler()->filter('.dash-progress-map')->count());
        // progress map
        self::assertSelectorExists('.map-marker.is-done');
        self::assertSelectorExists('.map-marker.is-current');
    }
}
