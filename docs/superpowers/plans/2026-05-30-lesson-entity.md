# Lesson Entity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `Lesson` Doctrine entity (video lesson inside a `Module`), its ManyToOne relation to `Module`, the inverse OneToMany collection on `Module`, a `getDurationFormatted()` helper, and a `findByModuleOrdered()` repository helper, all under TDD.

**Architecture:** Standard Doctrine ORM ManyToOne relation. `Lesson.module` is the owning side (FK column `module_id`); `Module.lessons` is the inverse Collection ordered by `displayOrder`. Cascade `persist` + `remove` + `orphanRemoval=true` so deleting a Module deletes its Lessons. Duration is stored as integer seconds; helper renders `"12 min 34 s"`. Tests boot the kernel against the in-memory SQLite test database.

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, PHPUnit 11, PHP 8.5, MySQL 8 (dev), SQLite (test).

---

## File Structure

**Created in this plan:**
- `src/Entity/Lesson.php` — Lesson entity (id, module FK, title, slug, vimeoVideoId, description, durationSeconds, displayOrder, timestamps)
- `src/Repository/LessonRepository.php` — `findByModuleOrdered(Module)`
- `tests/Entity/LessonTest.php` — unit tests for Lesson behaviour
- `tests/Repository/LessonRepositoryTest.php` — kernel integration test
- `migrations/Version<TIMESTAMP>.php` — Doctrine migration for `lesson` table

**Modified:**
- `src/Entity/Module.php` — add `$lessons` Collection + `addLesson()` / `removeLesson()` / `getLessons()` + `OrderBy` annotation
- `tests/Entity/ModuleTest.php` — add test for the new `getLessons()` collection initial state

---

### Task 1: Make `Module` ready to own a `Collection<Lesson>`

**Files:**
- Modify: `app/tests/Entity/ModuleTest.php`
- Modify: `app/src/Entity/Module.php`

- [ ] **Step 1: Add the failing test**

Open `tests/Entity/ModuleTest.php` and append this test inside the class (just before the closing `}`):

```php
    public function testGetLessonsReturnsEmptyCollectionOnConstruct(): void
    {
        $module = new \App\Entity\Module();

        self::assertCount(0, $module->getLessons());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $module->getLessons());
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run from inside `app/`:

```
php bin/phpunit tests/Entity/ModuleTest.php
```

Expected: 1 failing test with `Error: Call to undefined method App\Entity\Module::getLessons()`.

- [ ] **Step 3: Initialize the `lessons` Collection on `Module`**

In `src/Entity/Module.php`, add these imports at the top of the `use` block (right after the existing `use Doctrine\ORM\Mapping as ORM;` line):

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add this property right after the existing `$updatedAt` property:

```php
    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'module', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $lessons;
```

Replace the existing constructor with this version (initializes `lessons`):

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lessons = new ArrayCollection();
    }
```

Add these three methods at the end of the class (before the closing `}`):

```php
    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setModule($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson) && $lesson->getModule() === $this) {
            $lesson->setModule(null);
        }

        return $this;
    }
```

- [ ] **Step 4: The test still fails — `App\Entity\Lesson` does not exist yet**

Run:

```
php bin/phpunit tests/Entity/ModuleTest.php
```

Expected: `Error: Class "App\Entity\Lesson" not found`. This is intentional — proceed to Task 2 to create `Lesson`. Do not commit yet.

---

### Task 2: Create the `Lesson` entity under TDD

**Files:**
- Create: `app/tests/Entity/LessonTest.php`
- Create: `app/src/Entity/Lesson.php`
- Create: `app/src/Repository/LessonRepository.php`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Entity/LessonTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lesson;
use App\Entity\Module;
use PHPUnit\Framework\TestCase;

final class LessonTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $lesson = new Lesson();

        self::assertNull($lesson->getId());
        self::assertNull($lesson->getModule());
        self::assertSame(0, $lesson->getDisplayOrder());
        self::assertSame(0, $lesson->getDurationSeconds());
        self::assertInstanceOf(\DateTimeImmutable::class, $lesson->getCreatedAt());
        self::assertNull($lesson->getUpdatedAt());
    }

    public function testModuleIsAssignedThroughModuleAddLesson(): void
    {
        $module = new Module();
        $lesson = (new Lesson())
            ->setTitle('Bienvenue')
            ->setSlug('bienvenue')
            ->setVimeoVideoId('999111222')
            ->setDisplayOrder(1);

        $module->addLesson($lesson);

        self::assertSame($module, $lesson->getModule());
        self::assertCount(1, $module->getLessons());
    }

    public function testRemoveLessonDetachesIt(): void
    {
        $module = new Module();
        $lesson = (new Lesson())->setTitle('A')->setSlug('a')->setVimeoVideoId('111');
        $module->addLesson($lesson);

        $module->removeLesson($lesson);

        self::assertNull($lesson->getModule());
        self::assertCount(0, $module->getLessons());
    }

    public function testGetDurationFormattedRendersMinutesAndSeconds(): void
    {
        $lesson = (new Lesson())->setDurationSeconds(754);

        self::assertSame('12 min 34 s', $lesson->getDurationFormatted());
    }

    public function testGetDurationFormattedHandlesUnderOneMinute(): void
    {
        $lesson = (new Lesson())->setDurationSeconds(42);

        self::assertSame('0 min 42 s', $lesson->getDurationFormatted());
    }

    public function testGetDurationFormattedHandlesExactlyZero(): void
    {
        $lesson = new Lesson();

        self::assertSame('0 min 0 s', $lesson->getDurationFormatted());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/LessonTest.php
```

Expected: 6 failing tests with `Error: Class "App\Entity\Lesson" not found`.

- [ ] **Step 3: Create the placeholder repository**

Create `src/Repository/LessonRepository.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 */
final class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }
}
```

- [ ] **Step 4: Create the `Lesson` entity**

Create `src/Entity/Lesson.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
#[ORM\Table(name: 'lesson')]
#[ORM\UniqueConstraint(name: 'UNIQ_lesson_module_slug', columns: ['module_id', 'slug'])]
#[ORM\HasLifecycleCallbacks]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Module $module = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 180)]
    private ?string $slug = null;

    #[ORM\Column(length: 80)]
    private ?string $vimeoVideoId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $durationSeconds = 0;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getVimeoVideoId(): ?string
    {
        return $this->vimeoVideoId;
    }

    public function setVimeoVideoId(string $vimeoVideoId): static
    {
        $this->vimeoVideoId = $vimeoVideoId;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;
        return $this;
    }

    public function getDurationFormatted(): string
    {
        $minutes = intdiv($this->durationSeconds, 60);
        $seconds = $this->durationSeconds % 60;

        return sprintf('%d min %d s', $minutes, $seconds);
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function markUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

- [ ] **Step 5: Run both test files and verify they pass**

Run:

```
php bin/phpunit tests/Entity/LessonTest.php tests/Entity/ModuleTest.php
```

Expected: 6 Lesson tests pass, 4 Module tests pass (3 existing + the new `testGetLessonsReturnsEmptyCollectionOnConstruct`). No previous test regresses.

- [ ] **Step 6: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Lesson.php src/Repository/LessonRepository.php tests/Entity/LessonTest.php src/Entity/Module.php tests/Entity/ModuleTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(lesson): entity + Module OneToMany + getDurationFormatted helper"
```

---

### Task 3: Add `LessonRepository::findByModuleOrdered()` under TDD

**Files:**
- Create: `app/tests/Repository/LessonRepositoryTest.php`
- Modify: `app/src/Repository/LessonRepository.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/Repository/LessonRepositoryTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LessonRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LessonRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(LessonRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByModuleOrderedReturnsLessonsSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $module = (new Module())->setTitle('Démarrer')->setSlug('demarrer')->setDisplayOrder(1);
        $formation->addModule($module);

        $second = (new Lesson())->setTitle('Second')->setSlug('second')->setVimeoVideoId('222')->setDisplayOrder(2);
        $first  = (new Lesson())->setTitle('First') ->setSlug('first') ->setVimeoVideoId('111')->setDisplayOrder(1);
        $third  = (new Lesson())->setTitle('Third') ->setSlug('third') ->setVimeoVideoId('333')->setDisplayOrder(3);

        $module->addLesson($second);
        $module->addLesson($first);
        $module->addLesson($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByModuleOrdered($module);

        self::assertCount(3, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
        self::assertSame('third', $result[2]->getSlug());
    }

    public function testFindByModuleOrderedReturnsEmptyArrayWhenModuleHasNoLessons(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $module = (new Module())->setTitle('Empty Module')->setSlug('empty');
        $formation->addModule($module);

        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByModuleOrdered($module));
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/LessonRepositoryTest.php
```

Expected: 2 failing tests with `Error: Call to undefined method App\Repository\LessonRepository::findByModuleOrdered()` (raised via Doctrine `InvalidMagicMethodCall`).

- [ ] **Step 3: Implement `findByModuleOrdered`**

Open `src/Repository/LessonRepository.php` and replace its content with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 */
final class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * @return Lesson[]
     */
    public function findByModuleOrdered(Module $module): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.module = :module')
            ->setParameter('module', $module)
            ->orderBy('l.displayOrder', 'ASC')
            ->addOrderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/LessonRepositoryTest.php
```

Expected: 2 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/LessonRepository.php tests/Repository/LessonRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(lesson): repository findByModuleOrdered"
```

---

### Task 4: Generate and apply the `lesson` table migration

**Files:**
- Create: `app/migrations/Version<TIMESTAMP>.php` (timestamp auto-generated)

- [ ] **Step 1: Generate the migration**

Run:

```
php bin/console make:migration
```

Expected: console reports `created: migrations/VersionYYYYMMDDHHMMSS.php` (one new file).

- [ ] **Step 2: Verify the migration contains the `lesson` table**

Run:

```
LATEST=$(ls -t migrations/ | head -1) && grep -E 'CREATE TABLE lesson|module_id|UNIQ_lesson_module_slug|vimeo_video_id|duration_seconds' migrations/$LATEST
```

Expected: at least these matches appear, including the `CREATE TABLE lesson (...)` statement, the unique index on `(module_id, slug)`, `vimeo_video_id` column, and `duration_seconds` column.

- [ ] **Step 3: Apply the migration against the dev MAMP database**

Run:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully migrated to version: DoctrineMigrations\Version...`.

- [ ] **Step 4: Verify the table in MAMP**

Run:

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech -e "DESCRIBE lesson;"
```

Expected: one row per column including `module_id` (foreign key), `vimeo_video_id`, `duration_seconds`, `slug`, `display_order`, `created_at`, `updated_at`.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add migrations/
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(lesson): add migration for lesson table"
```

---

### Task 5: Final full-suite verification + milestone

**Files:**
- None modified.

- [ ] **Step 1: Run the entire PHPUnit suite**

Run:

```
php bin/phpunit
```

Expected: All tests pass. With Lesson added: Formation 5, Module 4, Lesson 6, FormationRepo 3, ModuleRepo 2, LessonRepo 2 → 22 tests minimum. Zero failures, zero errors.

- [ ] **Step 2: Run `doctrine:schema:validate`**

Run:

```
php bin/console doctrine:schema:validate
```

Expected: `[Mapping] OK - The mapping files are correct.` and `[Database] OK - The database schema is in sync with the mapping files.`

- [ ] **Step 3: Tag the milestone**

Run:

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit --allow-empty -m "milestone: Phase 1.4 Lesson entity complete"
```

---

## Self-Review

**Spec coverage:** Lesson entity columns (id, module FK, title, slug, vimeoVideoId, description, durationSeconds, displayOrder, timestamps) are covered in Task 2. `Module.lessons` Collection + add/remove methods covered in Task 1. `getDurationFormatted()` helper covered in Task 2 (3 dedicated tests covering normal case, under-one-minute case, zero case). `findByModuleOrdered` covered in Task 3. Migration in Task 4. Cascade `persist` + `remove` + `orphanRemoval=true` wired in Task 1 Step 3.

**Placeholder scan:** No `TODO`, no "implement later", no "similar to Task N", no orphan references. Every code block is complete and copy-pasteable.

**Type consistency:** `Lesson` is the only referenced class. `Module::addLesson(Lesson $lesson)` and `Module::removeLesson(Lesson $lesson)` match the calls in `LessonTest`. `findByModuleOrdered(Module $module): array` signature matches the call site in `LessonRepositoryTest`. `durationSeconds` and `displayOrder` stay `int` everywhere. `vimeoVideoId` is a `string` (not int) — Vimeo IDs can be large enough to overflow PHP int on 32-bit and we keep them opaque.

---

## Execution Handoff

Plan complete and saved to `app/docs/superpowers/plans/2026-05-30-lesson-entity.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
