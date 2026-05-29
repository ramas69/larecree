# Resource Entity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `Resource` Doctrine entity (external link OR uploaded file attached to a `Lesson`), its ManyToOne relation to `Lesson`, the inverse OneToMany collection on `Lesson`, a `ResourceType` PHP 8.1 backed enum (`link` / `file`), domain validation enforcing that `url` is set for `link` resources and `filePath` is set for `file` resources, and a `findByLessonOrdered()` repository helper, all under TDD.

**Architecture:** Standard Doctrine ORM ManyToOne relation. `Resource.lesson` is the owning side (FK column `lesson_id`); `Lesson.resources` is the inverse Collection ordered by `displayOrder`. Cascade `persist` + `remove` + `orphanRemoval=true`. `Resource.type` is a PHP 8.1 backed enum stored as a string column. Validation lives on the entity through dedicated Symfony Validator constraints (`#[Assert\Callback]`), allowing tests to run pure unit-level via `ValidatorBuilder` without booting the kernel.

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, Symfony Validator 7.4, PHPUnit 11, PHP 8.5, MySQL 8 (dev), SQLite (test).

---

## File Structure

**Created in this plan:**
- `src/Entity/Resource.php` — Resource entity (id, lesson FK, type, title, url, filePath, displayOrder, createdAt)
- `src/Entity/ResourceType.php` — PHP 8.1 backed enum (`Link`, `File`)
- `src/Repository/ResourceRepository.php` — `findByLessonOrdered(Lesson)`
- `tests/Entity/ResourceTypeTest.php` — enum cases coverage
- `tests/Entity/ResourceTest.php` — Resource unit tests including validation
- `tests/Repository/ResourceRepositoryTest.php` — kernel integration test
- `migrations/Version<TIMESTAMP>.php` — Doctrine migration for `resource` table

**Modified:**
- `src/Entity/Lesson.php` — add `$resources` Collection + `addResource()` / `removeResource()` / `getResources()` + `OrderBy`
- `tests/Entity/LessonTest.php` — add test for the new `getResources()` collection initial state

---

### Task 1: Create the `ResourceType` enum under TDD

**Files:**
- Create: `app/tests/Entity/ResourceTypeTest.php`
- Create: `app/src/Entity/ResourceType.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/ResourceTypeTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ResourceType;
use PHPUnit\Framework\TestCase;

final class ResourceTypeTest extends TestCase
{
    public function testEnumHasLinkAndFileCases(): void
    {
        self::assertSame('link', ResourceType::Link->value);
        self::assertSame('file', ResourceType::File->value);
    }

    public function testFromStringResolvesEnumCase(): void
    {
        self::assertSame(ResourceType::Link, ResourceType::from('link'));
        self::assertSame(ResourceType::File, ResourceType::from('file'));
    }

    public function testCasesReturnsExactlyTwoCases(): void
    {
        self::assertCount(2, ResourceType::cases());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run from inside `app/`:

```
php bin/phpunit tests/Entity/ResourceTypeTest.php
```

Expected: 3 failing tests with `Error: Class "App\Entity\ResourceType" not found`.

- [ ] **Step 3: Create the enum**

Create `src/Entity/ResourceType.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

enum ResourceType: string
{
    case Link = 'link';
    case File = 'file';
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Entity/ResourceTypeTest.php
```

Expected: 3 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/ResourceType.php tests/Entity/ResourceTypeTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(resource): ResourceType enum (Link/File)"
```

---

### Task 2: Make `Lesson` ready to own a `Collection<Resource>`

**Files:**
- Modify: `app/tests/Entity/LessonTest.php`
- Modify: `app/src/Entity/Lesson.php`

- [ ] **Step 1: Add the failing test**

Open `tests/Entity/LessonTest.php` and append this test inside the class (just before the closing `}`):

```php
    public function testGetResourcesReturnsEmptyCollectionOnConstruct(): void
    {
        $lesson = new Lesson();

        self::assertCount(0, $lesson->getResources());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $lesson->getResources());
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/LessonTest.php
```

Expected: 1 failing test with `Error: Call to undefined method App\Entity\Lesson::getResources()`.

- [ ] **Step 3: Initialize the `resources` Collection on `Lesson`**

In `src/Entity/Lesson.php`, add these imports at the top of the `use` block (right after the existing `use Doctrine\ORM\Mapping as ORM;` line):

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add this property right after the existing `$updatedAt` property:

```php
    /**
     * @var Collection<int, Resource>
     */
    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'lesson', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $resources;
```

Replace the existing constructor with this version (initializes `resources`):

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->resources = new ArrayCollection();
    }
```

Add these three methods at the end of the class (before the closing `}`):

```php
    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setLesson($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource) && $resource->getLesson() === $this) {
            $resource->setLesson(null);
        }

        return $this;
    }
```

- [ ] **Step 4: The test still fails — `App\Entity\Resource` does not exist yet**

Run:

```
php bin/phpunit tests/Entity/LessonTest.php
```

Expected: `Error: Class "App\Entity\Resource" not found`. This is intentional — Task 3 introduces `Resource`. Do not commit yet.

---

### Task 3: Create the `Resource` entity under TDD

**Files:**
- Create: `app/tests/Entity/ResourceTest.php`
- Create: `app/src/Entity/Resource.php`
- Create: `app/src/Repository/ResourceRepository.php`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Entity/ResourceTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lesson;
use App\Entity\Resource;
use App\Entity\ResourceType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ResourceTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $resource = new Resource();

        self::assertNull($resource->getId());
        self::assertNull($resource->getLesson());
        self::assertSame(0, $resource->getDisplayOrder());
        self::assertNull($resource->getType());
        self::assertNull($resource->getUrl());
        self::assertNull($resource->getFilePath());
        self::assertInstanceOf(\DateTimeImmutable::class, $resource->getCreatedAt());
    }

    public function testLessonIsAssignedThroughLessonAddResource(): void
    {
        $lesson = new Lesson();
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc Anthropic')
            ->setUrl('https://docs.anthropic.com');

        $lesson->addResource($resource);

        self::assertSame($lesson, $resource->getLesson());
        self::assertCount(1, $lesson->getResources());
    }

    public function testRemoveResourceDetachesIt(): void
    {
        $lesson = new Lesson();
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF')
            ->setFilePath('/uploads/x.pdf');
        $lesson->addResource($resource);

        $lesson->removeResource($resource);

        self::assertNull($resource->getLesson());
        self::assertCount(0, $lesson->getResources());
    }

    public function testValidationFailsWhenLinkResourceHasNoUrl(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('url', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWhenFileResourceHasNoFilePath(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('filePath', $violations[0]->getPropertyPath());
    }

    public function testValidationPassesWhenLinkHasUrl(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc')
            ->setUrl('https://example.com');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertCount(0, $violations);
    }

    public function testValidationPassesWhenFileHasFilePath(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF')
            ->setFilePath('/uploads/x.pdf');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertCount(0, $violations);
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/ResourceTest.php
```

Expected: 7 failing tests with `Error: Class "App\Entity\Resource" not found`.

- [ ] **Step 3: Create the placeholder repository**

Create `src/Repository/ResourceRepository.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
final class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }
}
```

- [ ] **Step 4: Create the `Resource` entity**

Create `src/Entity/Resource.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resource')]
#[ORM\HasLifecycleCallbacks]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 16, enumType: ResourceType::class)]
    private ?ResourceType $type = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?ResourceType
    {
        return $this->type;
    }

    public function setType(?ResourceType $type): static
    {
        $this->type = $type;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
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

    #[Assert\Callback]
    public function validateTypePayload(ExecutionContextInterface $context): void
    {
        if ($this->type === ResourceType::Link && ($this->url === null || $this->url === '')) {
            $context->buildViolation('A link resource must have a url.')
                ->atPath('url')
                ->addViolation();
        }

        if ($this->type === ResourceType::File && ($this->filePath === null || $this->filePath === '')) {
            $context->buildViolation('A file resource must have a filePath.')
                ->atPath('filePath')
                ->addViolation();
        }
    }
}
```

- [ ] **Step 5: Run all entity tests and verify they pass**

Run:

```
php bin/phpunit tests/Entity/ResourceTest.php tests/Entity/LessonTest.php tests/Entity/ResourceTypeTest.php
```

Expected: 7 Resource tests pass + 7 Lesson tests pass + 3 ResourceType tests pass. No previous test regresses.

- [ ] **Step 6: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Resource.php src/Repository/ResourceRepository.php tests/Entity/ResourceTest.php src/Entity/Lesson.php tests/Entity/LessonTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(resource): entity + Lesson OneToMany + Link/File validation"
```

---

### Task 4: Add `ResourceRepository::findByLessonOrdered()` under TDD

**Files:**
- Create: `app/tests/Repository/ResourceRepositoryTest.php`
- Modify: `app/src/Repository/ResourceRepository.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/Repository/ResourceRepositoryTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Resource;
use App\Entity\ResourceType;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ResourceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ResourceRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(ResourceRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByLessonOrderedReturnsResourcesSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $module    = (new Module())->setTitle('Démarrer')->setSlug('demarrer');
        $lesson    = (new Lesson())->setTitle('Intro')->setSlug('intro')->setVimeoVideoId('111');

        $formation->addModule($module);
        $module->addLesson($lesson);

        $second = (new Resource())->setType(ResourceType::Link)->setTitle('Doc')->setUrl('https://docs.example.com')->setDisplayOrder(2);
        $first  = (new Resource())->setType(ResourceType::File)->setTitle('PDF')->setFilePath('/uploads/a.pdf')->setDisplayOrder(1);
        $third  = (new Resource())->setType(ResourceType::Link)->setTitle('Notion')->setUrl('https://notion.so/x')->setDisplayOrder(3);

        $lesson->addResource($second);
        $lesson->addResource($first);
        $lesson->addResource($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByLessonOrdered($lesson);

        self::assertCount(3, $result);
        self::assertSame('PDF', $result[0]->getTitle());
        self::assertSame('Doc', $result[1]->getTitle());
        self::assertSame('Notion', $result[2]->getTitle());
    }

    public function testFindByLessonOrderedReturnsEmptyArrayWhenLessonHasNoResources(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $module    = (new Module())->setTitle('Empty Module')->setSlug('empty');
        $lesson    = (new Lesson())->setTitle('Empty Lesson')->setSlug('empty')->setVimeoVideoId('999');

        $formation->addModule($module);
        $module->addLesson($lesson);

        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByLessonOrdered($lesson));
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/ResourceRepositoryTest.php
```

Expected: 2 failing tests with `Error: Call to undefined method App\Repository\ResourceRepository::findByLessonOrdered()` (raised via Doctrine `InvalidMagicMethodCall`).

- [ ] **Step 3: Implement `findByLessonOrdered`**

Open `src/Repository/ResourceRepository.php` and replace its content with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
final class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    /**
     * @return Resource[]
     */
    public function findByLessonOrdered(Lesson $lesson): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.lesson = :lesson')
            ->setParameter('lesson', $lesson)
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/ResourceRepositoryTest.php
```

Expected: 2 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/ResourceRepository.php tests/Repository/ResourceRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(resource): repository findByLessonOrdered"
```

---

### Task 5: Generate and apply the `resource` table migration

**Files:**
- Create: `app/migrations/Version<TIMESTAMP>.php` (timestamp auto-generated)

- [ ] **Step 1: Generate the migration**

Run:

```
php bin/console make:migration
```

Expected: console reports `created: migrations/VersionYYYYMMDDHHMMSS.php` (one new file).

- [ ] **Step 2: Verify the migration contains the `resource` table**

Run:

```
LATEST=$(ls -t migrations/ | head -1) && grep -E 'CREATE TABLE resource|lesson_id|file_path|display_order' migrations/$LATEST
```

Expected: matches appear, including `CREATE TABLE resource (...)`, the `lesson_id` foreign key column, `file_path` column, and `display_order` column.

- [ ] **Step 3: Apply the migration against the dev MAMP database**

Run:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully migrated to version: DoctrineMigrations\Version...`.

- [ ] **Step 4: Verify the table in MAMP**

Run:

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech -e "DESCRIBE resource;"
```

Expected: one row per column including `lesson_id` (foreign key), `type`, `title`, `url`, `file_path`, `display_order`, `created_at`.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add migrations/
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(resource): add migration for resource table"
```

---

### Task 6: Final full-suite verification + milestone

**Files:**
- None modified.

- [ ] **Step 1: Run the entire PHPUnit suite**

Run:

```
php bin/phpunit
```

Expected: All tests pass. With Resource added: Formation 5, Module 4, Lesson 7, Resource 7, ResourceType 3, FormationRepo 3, ModuleRepo 2, LessonRepo 2, ResourceRepo 2 → 35 tests minimum. Zero failures, zero errors.

- [ ] **Step 2: Run `doctrine:schema:validate`**

Run:

```
php bin/console doctrine:schema:validate
```

Expected: `[Mapping] OK - The mapping files are correct.` and `[Database] OK - The database schema is in sync with the mapping files.`

- [ ] **Step 3: Tag the milestone**

Run:

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit --allow-empty -m "milestone: Phase 1.5 Resource entity complete"
```

---

## Self-Review

**Spec coverage:** Resource entity columns (id, lesson FK, type, title, url, filePath, displayOrder, createdAt) are covered in Task 3. `ResourceType` enum covered in Task 1. `Lesson.resources` Collection covered in Task 2. Validation that `url` is required for `link` and `filePath` is required for `file` is covered by 4 dedicated tests in Task 3 (fail when missing on both branches, pass when present on both branches). `findByLessonOrdered` covered in Task 4. Migration in Task 5. Cascade `persist` + `remove` + `orphanRemoval=true` wired in Task 2 Step 3.

**Placeholder scan:** No `TODO`, no "implement later", no "similar to Task N", no orphan references. Every code block is complete and copy-pasteable.

**Type consistency:** `Resource` is the only referenced entity class. `Lesson::addResource(Resource $resource)` and `Lesson::removeResource(Resource $resource)` match the calls in `ResourceTest`. `findByLessonOrdered(Lesson $lesson): array` signature matches the call site in `ResourceRepositoryTest`. `ResourceType::Link` and `ResourceType::File` cases are used identically across Tasks 1, 3, and 4. The `Resource::$type` column declares `enumType: ResourceType::class` so Doctrine round-trips the enum without manual conversion.

---

## Execution Handoff

Plan complete and saved to `app/docs/superpowers/plans/2026-05-30-resource-entity.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
