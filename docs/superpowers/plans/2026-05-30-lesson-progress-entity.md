# Phase 1.7 — LessonProgress Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `LessonProgress` entity tracking watched seconds + percent + completion timestamp per (Enrollment, Lesson) pair.

**Architecture:**
- `LessonProgress` is the join row between an `Enrollment` and a `Lesson`. Unique on `(enrollment_id, lesson_id)`.
- Owning side: `LessonProgress` holds both ManyToOne FKs with `onDelete: CASCADE`. Inverse Collections live on `Enrollment.progresses` and `Lesson.progresses`.
- Helpers `isCompleted()` and `markCompleted()` keep state transitions encapsulated; `recordWatch(seconds, percent)` bumps `lastWatchedAt`.

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, PHPUnit 11, PHP 8.5, MAMP MySQL 8.0.44 (dev), SQLite in-memory (test).

---

## File map

- Create: `src/Entity/LessonProgress.php`
- Create: `src/Repository/LessonProgressRepository.php`
- Create: `tests/Entity/LessonProgressTest.php`
- Create: `tests/Repository/LessonProgressRepositoryTest.php`
- Modify: `src/Entity/Enrollment.php` — add inverse `Collection $progresses`
- Modify: `src/Entity/Lesson.php` — add inverse `Collection $progresses`
- Modify: `tests/Entity/EnrollmentTest.php` — add construct-empty assertion
- Modify: `tests/Entity/LessonTest.php` (if exists, else create) — add construct-empty assertion
- Create: `migrations/Version<ts>.php` (generated)

---

## Task 1: Enrollment.progresses inverse Collection

**Files:**
- Modify: `src/Entity/Enrollment.php`
- Modify: `tests/Entity/EnrollmentTest.php` (if exists, else create)

- [ ] **Step 1: Add construct-empty test**

```php
public function testGetProgressesReturnsEmptyCollectionOnConstruct(): void
{
    $enrollment = new Enrollment();
    self::assertCount(0, $enrollment->getProgresses());
}
```

- [ ] **Step 2: Run — expect fail (method not defined)**

```
php bin/phpunit tests/Entity/EnrollmentTest.php
```

- [ ] **Step 3: Add Collection import + field + init in constructor + getter/adder/remover**

In `Enrollment.php`, after existing `use Doctrine\ORM\Mapping as ORM;` block add (if not present):

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add property after `$createdAt`:

```php
    /**
     * @var Collection<int, LessonProgress>
     */
    #[ORM\OneToMany(targetEntity: LessonProgress::class, mappedBy: 'enrollment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $progresses;
```

Update constructor:

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->progresses = new ArrayCollection();
    }
```

Append methods at end of class:

```php
    /**
     * @return Collection<int, LessonProgress>
     */
    public function getProgresses(): Collection
    {
        return $this->progresses;
    }

    public function addProgress(LessonProgress $progress): static
    {
        if (!$this->progresses->contains($progress)) {
            $this->progresses->add($progress);
            $progress->setEnrollment($this);
        }

        return $this;
    }

    public function removeProgress(LessonProgress $progress): static
    {
        if ($this->progresses->removeElement($progress) && $progress->getEnrollment() === $this) {
            $progress->setEnrollment(null);
        }

        return $this;
    }
```

- [ ] **Step 4: Run — still fails (LessonProgress class not found)**

That is expected — Task 3 creates it. Leave failing.

---

## Task 2: Lesson.progresses inverse Collection

**Files:**
- Modify: `src/Entity/Lesson.php`
- Modify (or create): `tests/Entity/LessonTest.php`

- [ ] **Step 1: Add construct-empty test**

If `tests/Entity/LessonTest.php` does not exist, create it:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lesson;
use PHPUnit\Framework\TestCase;

final class LessonTest extends TestCase
{
    public function testGetProgressesReturnsEmptyCollectionOnConstruct(): void
    {
        $lesson = new Lesson();
        self::assertCount(0, $lesson->getProgresses());
    }
}
```

If it already exists, append the single test method.

- [ ] **Step 2: Run — expect fail**

```
php bin/phpunit tests/Entity/LessonTest.php
```

- [ ] **Step 3: Add inverse Collection on Lesson**

In `Lesson.php` ensure imports include:

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add property after `$resources`:

```php
    /**
     * @var Collection<int, LessonProgress>
     */
    #[ORM\OneToMany(targetEntity: LessonProgress::class, mappedBy: 'lesson', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $progresses;
```

In constructor, init alongside existing initializations:

```php
        $this->progresses = new ArrayCollection();
```

Append methods at end:

```php
    /**
     * @return Collection<int, LessonProgress>
     */
    public function getProgresses(): Collection
    {
        return $this->progresses;
    }

    public function addProgress(LessonProgress $progress): static
    {
        if (!$this->progresses->contains($progress)) {
            $this->progresses->add($progress);
            $progress->setLesson($this);
        }

        return $this;
    }

    public function removeProgress(LessonProgress $progress): static
    {
        if ($this->progresses->removeElement($progress) && $progress->getLesson() === $this) {
            $progress->setLesson(null);
        }

        return $this;
    }
```

- [ ] **Step 4: Run — still fails (LessonProgress missing)**

Expected. Task 3 fixes.

---

## Task 3: LessonProgress entity (TDD)

**Files:**
- Create: `tests/Entity/LessonProgressTest.php`
- Create: `src/Entity/LessonProgress.php`
- Create: `src/Repository/LessonProgressRepository.php`

- [ ] **Step 1: Write entity tests**

Write `tests/Entity/LessonProgressTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use PHPUnit\Framework\TestCase;

final class LessonProgressTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $progress = new LessonProgress();

        self::assertNull($progress->getId());
        self::assertNull($progress->getEnrollment());
        self::assertNull($progress->getLesson());
        self::assertSame(0, $progress->getWatchedSeconds());
        self::assertSame(0, $progress->getPercentWatched());
        self::assertNull($progress->getCompletedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getLastWatchedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getCreatedAt());
        self::assertFalse($progress->isCompleted());
    }

    public function testAddProgressToEnrollmentBindsBothSides(): void
    {
        $enrollment = new Enrollment();
        $progress = new LessonProgress();

        $enrollment->addProgress($progress);

        self::assertSame($enrollment, $progress->getEnrollment());
        self::assertCount(1, $enrollment->getProgresses());
    }

    public function testAddProgressToLessonBindsBothSides(): void
    {
        $lesson = new Lesson();
        $progress = new LessonProgress();

        $lesson->addProgress($progress);

        self::assertSame($lesson, $progress->getLesson());
        self::assertCount(1, $lesson->getProgresses());
    }

    public function testRecordWatchUpdatesSecondsPercentAndLastWatchedAt(): void
    {
        $progress = new LessonProgress();
        $before = $progress->getLastWatchedAt();
        usleep(2_000);

        $progress->recordWatch(120, 50);

        self::assertSame(120, $progress->getWatchedSeconds());
        self::assertSame(50, $progress->getPercentWatched());
        self::assertGreaterThan($before, $progress->getLastWatchedAt());
        self::assertNull($progress->getCompletedAt());
    }

    public function testRecordWatchClampsPercentBetween0And100(): void
    {
        $progress = new LessonProgress();

        $progress->recordWatch(10, -5);
        self::assertSame(0, $progress->getPercentWatched());

        $progress->recordWatch(10, 250);
        self::assertSame(100, $progress->getPercentWatched());
    }

    public function testRecordWatchRefusesNegativeSeconds(): void
    {
        $progress = new LessonProgress();

        $this->expectException(\InvalidArgumentException::class);
        $progress->recordWatch(-1, 0);
    }

    public function testMarkCompletedSetsTimestampPercent100AndIsCompletedTrue(): void
    {
        $progress = new LessonProgress();

        $progress->markCompleted();

        self::assertTrue($progress->isCompleted());
        self::assertSame(100, $progress->getPercentWatched());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getCompletedAt());
    }

    public function testMarkCompletedIsIdempotent(): void
    {
        $progress = new LessonProgress();

        $progress->markCompleted();
        $first = $progress->getCompletedAt();
        usleep(2_000);
        $progress->markCompleted();

        self::assertSame($first, $progress->getCompletedAt());
    }
}
```

- [ ] **Step 2: Run — expect fail (class missing)**

```
php bin/phpunit tests/Entity/LessonProgressTest.php
```

- [ ] **Step 3: Create placeholder repository so entity attribute resolves**

Write `src/Repository/LessonProgressRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LessonProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonProgress>
 */
final class LessonProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonProgress::class);
    }
}
```

- [ ] **Step 4: Write the entity**

Write `src/Entity/LessonProgress.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonProgressRepository::class)]
#[ORM\Table(name: 'lesson_progress')]
#[ORM\UniqueConstraint(name: 'UNIQ_lesson_progress_enrollment_lesson', columns: ['enrollment_id', 'lesson_id'])]
class LessonProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Enrollment $enrollment = null;

    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column]
    private int $watchedSeconds = 0;

    #[ORM\Column]
    private int $percentWatched = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $lastWatchedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastWatchedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment;
    }

    public function setEnrollment(?Enrollment $enrollment): static
    {
        $this->enrollment = $enrollment;
        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): static
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getWatchedSeconds(): int
    {
        return $this->watchedSeconds;
    }

    public function getPercentWatched(): int
    {
        return $this->percentWatched;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getLastWatchedAt(): \DateTimeImmutable
    {
        return $this->lastWatchedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function recordWatch(int $seconds, int $percent): static
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('watchedSeconds must be >= 0.');
        }

        $this->watchedSeconds = $seconds;
        $this->percentWatched = max(0, min(100, $percent));
        $this->lastWatchedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markCompleted(): static
    {
        if ($this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        }
        $this->percentWatched = 100;

        return $this;
    }
}
```

- [ ] **Step 5: Run entity tests — expect pass**

```
php bin/phpunit tests/Entity/LessonProgressTest.php tests/Entity/EnrollmentTest.php tests/Entity/LessonTest.php
```

Expected: all green.

- [ ] **Step 6: Commit Tasks 1+2+3**

```bash
git add src/Entity/LessonProgress.php src/Repository/LessonProgressRepository.php \
        src/Entity/Enrollment.php src/Entity/Lesson.php \
        tests/Entity/LessonProgressTest.php tests/Entity/EnrollmentTest.php tests/Entity/LessonTest.php
git commit -m "feat(lesson-progress): entity + Enrollment/Lesson collections + recordWatch/markCompleted helpers"
```

---

## Task 4: Repository finders

**Files:**
- Create: `tests/Repository/LessonProgressRepositoryTest.php`
- Modify: `src/Repository/LessonProgressRepository.php`

- [ ] **Step 1: Write repo tests**

Write `tests/Repository/LessonProgressRepositoryTest.php`:

```php
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
        $other      = $this->persistEnrollment('b@b.com');
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
        $module = (new Module())->setTitle('M')->setDisplayOrder($order);
        $formation->addModule($module);
        $this->em->persist($module);

        $lesson = (new Lesson())->setTitle('L'.$slug)->setSlug($slug)->setDisplayOrder($order);
        $module->addLesson($lesson);
        $this->em->persist($lesson);
        $this->em->flush();

        return $lesson;
    }
}
```

- [ ] **Step 2: Run — expect fail (finders missing)**

```
php bin/phpunit tests/Repository/LessonProgressRepositoryTest.php
```

- [ ] **Step 3: Implement finders**

Replace body of `LessonProgressRepository` (keep header / constructor) — add finders:

```php
    public function findOneByEnrollmentAndLesson(Enrollment $enrollment, Lesson $lesson): ?LessonProgress
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.enrollment = :enrollment')
            ->andWhere('p.lesson = :lesson')
            ->setParameter('enrollment', $enrollment)
            ->setParameter('lesson', $lesson)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return LessonProgress[]
     */
    public function findByEnrollment(Enrollment $enrollment): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.enrollment = :enrollment')
            ->setParameter('enrollment', $enrollment)
            ->orderBy('p.lastWatchedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countCompletedByEnrollment(Enrollment $enrollment): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.enrollment = :enrollment')
            ->andWhere('p.completedAt IS NOT NULL')
            ->setParameter('enrollment', $enrollment)
            ->getQuery()
            ->getSingleScalarResult();
    }
```

Add imports at top:

```php
use App\Entity\Enrollment;
use App\Entity\Lesson;
```

- [ ] **Step 4: Run — expect pass**

```
php bin/phpunit tests/Repository/LessonProgressRepositoryTest.php
```

- [ ] **Step 5: Commit**

```bash
git add src/Repository/LessonProgressRepository.php tests/Repository/LessonProgressRepositoryTest.php
git commit -m "feat(lesson-progress): repo finders findByEnrollment / findOneByEnrollmentAndLesson / countCompletedByEnrollment"
```

---

## Task 5: Migration

- [ ] **Step 1: Generate migration**

```
php bin/console make:migration --no-interaction
```

- [ ] **Step 2: Review** — must create `lesson_progress` with `UNIQ_lesson_progress_enrollment_lesson(enrollment_id, lesson_id)` and FKs on enrollment+lesson with `ON DELETE CASCADE`.

- [ ] **Step 3: Apply**

```
php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 4: Verify table in MAMP**

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -uroot -proot -h127.0.0.1 -P8889 -D larecreetech -e "SHOW CREATE TABLE lesson_progress\G"
```

Expected: unique key + 2 FKs ON DELETE CASCADE.

- [ ] **Step 5: Commit**

```bash
git add migrations/Version<ts>.php
git commit -m "feat(lesson-progress): migration — lesson_progress table + FKs + unique(enrollment_id, lesson_id)"
```

---

## Task 6: Final verify + milestone commit

- [ ] **Step 1: Full suite**

```
php bin/phpunit
```

Expected: all green (previous 50 + ~12 new ≈ 62).

- [ ] **Step 2: Schema validate**

```
php bin/console doctrine:schema:validate
```

Expected: mapping + database OK.

- [ ] **Step 3: Empty milestone commit**

```bash
git commit --allow-empty -m "chore(lesson-progress): phase 1.7 complete — entity, repo, migration, all tests green"
```

---

## Finishing

After Task 6: invoke `superpowers:finishing-a-development-branch`. Default = option 1 (merge to main locally, push, delete feature branch).
