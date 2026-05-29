<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EnrollmentRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EnrollmentRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(EnrollmentRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByUserReturnsAllEnrollmentsForThatUserNewestFirst(): void
    {
        $user      = $this->persistUser('a@b.com');
        $other     = $this->persistUser('c@d.com');
        $formationA = $this->persistFormation('claude', 'Claude');
        $formationB = $this->persistFormation('design', 'Design');

        $older = (new Enrollment())->setUser($user)->setFormation($formationA)->setSource(EnrollmentSource::Stripe);
        $newer = (new Enrollment())->setUser($user)->setFormation($formationB)->setSource(EnrollmentSource::Vip);
        $foreign = (new Enrollment())->setUser($other)->setFormation($formationA)->setSource(EnrollmentSource::Stripe);

        $this->em->persist($older);
        $this->em->flush();
        usleep(10_000);
        $this->em->persist($newer);
        $this->em->persist($foreign);
        $this->em->flush();

        $result = $this->repo->findByUser($user);

        self::assertCount(2, $result);
        self::assertSame($newer->getId(), $result[0]->getId());
        self::assertSame($older->getId(), $result[1]->getId());
    }

    public function testFindOneByUserAndFormationReturnsMatchOrNull(): void
    {
        $user      = $this->persistUser('a@b.com');
        $formation = $this->persistFormation('claude', 'Claude');
        $enrollment = (new Enrollment())->setUser($user)->setFormation($formation)->setSource(EnrollmentSource::Stripe);
        $this->em->persist($enrollment);
        $this->em->flush();

        $found = $this->repo->findOneByUserAndFormation($user, $formation);
        $other = $this->persistFormation('design', 'Design');
        $miss  = $this->repo->findOneByUserAndFormation($user, $other);

        self::assertNotNull($found);
        self::assertSame($enrollment->getId(), $found->getId());
        self::assertNull($miss);
    }

    public function testDuplicateUserFormationPairFailsAtUniqueConstraint(): void
    {
        $user      = $this->persistUser('a@b.com');
        $formation = $this->persistFormation('claude', 'Claude');

        $first  = (new Enrollment())->setUser($user)->setFormation($formation)->setSource(EnrollmentSource::Stripe);
        $second = (new Enrollment())->setUser($user)->setFormation($formation)->setSource(EnrollmentSource::Vip);

        $this->em->persist($first);
        $this->em->flush();

        $this->em->persist($second);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    private function persistUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('x');
        $user->setFirstName('First');
        $user->setLastName('Last');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function persistFormation(string $slug, string $title): Formation
    {
        $formation = (new Formation())->setSlug($slug)->setTitle($title);

        $this->em->persist($formation);
        $this->em->flush();

        return $formation;
    }
}
