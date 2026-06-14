<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\LessonProgressRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LessonProgressRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LessonProgressRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(LessonProgressRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindOneByEnrollmentAndLessonReturnsMatchOrNull(): void
    {
        $enrollment = $this->persistEnrollment();
        $lessonA = $this->persistLesson($enrollment->getFormation(), 'a', 1);
        $lessonB = $this->persistLesson($enrollment->getFormation(), 'b', 2);

        $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonA);
        $this->em->persist($progress);
        $this->em->flush();

        $hit  = $this->repo->findOneByEnrollmentAndLesson($enrollment, $lessonA);
        $miss = $this->repo->findOneByEnrollmentAndLesson($enrollment, $lessonB);

        self::assertNotNull($hit);
        self::assertSame($progress->getId(), $hit->getId());
        self::assertNull($miss);
    }

    public function testFindByEnrollmentReturnsAllRowsForThatEnrollmentNewestFirst(): void
    {
        $enrollment = $this->persistEnrollment();
        $other      = $this->persistEnrollment('b@b.com', 'design');
        $lessonA    = $this->persistLesson($enrollment->getFormation(), 'a', 1);
        $lessonB    = $this->persistLesson($enrollment->getFormation(), 'b', 2);

        $older  = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonA);
        $newer  = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonB);
        $foreign = (new LessonProgress())->setEnrollment($other)->setLesson($lessonA);

        $this->em->persist($older);
        $this->em->flush();
        usleep(10_000);
        $newer->recordWatch(30, 25);
        $this->em->persist($newer);
        $this->em->persist($foreign);
        $this->em->flush();

        $result = $this->repo->findByEnrollment($enrollment);

        self::assertCount(2, $result);
        self::assertSame($newer->getId(), $result[0]->getId());
        self::assertSame($older->getId(), $result[1]->getId());
    }

    public function testCountCompletedByEnrollmentReturnsOnlyCompletedRows(): void
    {
        $enrollment = $this->persistEnrollment();
        $lessonA    = $this->persistLesson($enrollment->getFormation(), 'a', 1);
        $lessonB    = $this->persistLesson($enrollment->getFormation(), 'b', 2);
        $lessonC    = $this->persistLesson($enrollment->getFormation(), 'c', 3);

        $done1 = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonA);
        $done1->markCompleted();
        $done2 = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonB);
        $done2->markCompleted();
        $inProgress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lessonC);
        $inProgress->recordWatch(30, 25);

        $this->em->persist($done1);
        $this->em->persist($done2);
        $this->em->persist($inProgress);
        $this->em->flush();

        self::assertSame(2, $this->repo->countCompletedByEnrollment($enrollment));
    }

    public function testDuplicateEnrollmentLessonPairFailsAtUniqueConstraint(): void
    {
        $enrollment = $this->persistEnrollment();
        $lesson     = $this->persistLesson($enrollment->getFormation(), 'a', 1);

        $first  = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
        $second = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);

        $this->em->persist($first);
        $this->em->flush();

        $this->em->persist($second);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    private function persistEnrollment(string $email = 'a@b.com', string $formationSlug = 'claude'): Enrollment
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('x');
        $user->setFirstName('First');
        $user->setLastName('Last');
        $this->em->persist($user);

        $formation = (new Formation())->setSlug($formationSlug)->setTitle('Claude');
        $this->em->persist($formation);
        $this->em->flush();

        $enrollment = (new Enrollment())->setUser($user)->setFormation($formation)->setSource(EnrollmentSource::Stripe);
        $this->em->persist($enrollment);
        $this->em->flush();

        return $enrollment;
    }

    private function persistLesson(Formation $formation, string $slug, int $order): Lesson
    {
        $module = (new Module())->setTitle('M-'.$slug)->setSlug('m-'.$slug)->setDisplayOrder($order);
        $formation->addModule($module);
        $this->em->persist($module);

        $lesson = (new Lesson())->setTitle('L-'.$slug)->setSlug($slug)->setDisplayOrder($order);
        $module->addLesson($lesson);
        $this->em->persist($lesson);
        $this->em->flush();

        return $lesson;
    }
}
