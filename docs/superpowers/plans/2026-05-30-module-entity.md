# Module Entity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `Module` Doctrine entity (chapter inside a `Formation`), its ManyToOne relation to `Formation`, the inverse OneToMany collection on `Formation`, and a `findByFormationOrdered()` repository helper, all under TDD.

**Architecture:** Standard Doctrine ORM ManyToOne relation. `Module.formation` is the owning side (FK column `formation_id`); `Formation.modules` is the inverse Collection ordered by `displayOrder`. Cascade `persist` + `remove` + `orphanRemoval=true` so deleting a Formation deletes its Modules. Tests boot the kernel against the test in-memory SQLite database (already configured in `.env.test`).

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, PHPUnit 11, PHP 8.5, MySQL 8 (dev), SQLite (test).

---

## File Structure

**Created in this plan:**
- `src/Entity/Module.php` — Module entity (id, formation FK, title, slug, description, displayOrder, createdAt, updatedAt)
- `src/Repository/ModuleRepository.php` — `findByFormationOrdered(Formation)`
- `tests/Entity/ModuleTest.php` — unit tests for Module behaviour
- `tests/Repository/ModuleRepositoryTest.php` — kernel integration test
- `migrations/Version<TIMESTAMP>.php` — Doctrine migration for `module` table

**Modified:**
- `src/Entity/Formation.php` — add `$modules` Collection + `addModule()` / `removeModule()` / `getModules()` + `OrderBy` annotation
- `tests/Entity/FormationTest.php` — add test for the new `getModules()` collection initial state

---

### Task 1: Make `Formation` ready to own a `Collection<Module>`

**Files:**
- Modify: `app/tests/Entity/FormationTest.php`
- Modify: `app/src/Entity/Formation.php`

- [ ] **Step 1: Add the failing test**

Open `tests/Entity/FormationTest.php` and append this test inside the class (just before the closing `}`):

```php
    public function testGetModulesReturnsEmptyCollectionOnConstruct(): void
    {
        $formation = new Formation();

        self::assertCount(0, $formation->getModules());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $formation->getModules());
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run from inside `app/`:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: 1 failing test with `Error: Call to undefined method App\Entity\Formation::getModules()`.

- [ ] **Step 3: Initialize the `modules` Collection on `Formation`**

In `src/Entity/Formation.php`, add the import at the top of the `use` block (right after the existing `use Doctrine\ORM\Mapping as ORM;` line):

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add this property after the existing `$updatedAt` property:

```php
    /**
     * @var Collection<int, Module>
     */
    #[ORM\OneToMany(targetEntity: Module::class, mappedBy: 'formation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $modules;
```

Replace the existing constructor with this version (initializes `modules`):

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modules = new ArrayCollection();
    }
```

Add these three methods at the end of the class (before the closing `}`):

```php
    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->setFormation($this);
        }

        return $this;
    }

    public function removeModule(Module $module): static
    {
        if ($this->modules->removeElement($module)) {
            if ($module->getFormation() === $this) {
                $module->setFormation(null);
            }
        }

        return $this;
    }
```

- [ ] **Step 4: The test still fails — `App\Entity\Module` does not exist yet**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: `Error: Class "App\Entity\Module" not found`. This is intentional — the failing test stays red until Task 2 introduces `Module`. Do not commit yet — proceed to Task 2.

---

### Task 2: Create the `Module` entity under TDD

**Files:**
- Create: `app/tests/Entity/ModuleTest.php`
- Create: `app/src/Entity/Module.php`
- Create: `app/src/Repository/ModuleRepository.php`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Entity/ModuleTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Module;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $module = new Module();

        self::assertNull($module->getId());
        self::assertNull($module->getFormation());
        self::assertSame(0, $module->getDisplayOrder());
        self::assertInstanceOf(\DateTimeImmutable::class, $module->getCreatedAt());
        self::assertNull($module->getUpdatedAt());
    }

    public function testFormationIsAssignedThroughFormationAddModule(): void
    {
        $formation = new Formation();
        $module = (new Module())
            ->setTitle('Démarrer avec Claude')
            ->setSlug('demarrer-claude')
            ->setDisplayOrder(1);

        $formation->addModule($module);

        self::assertSame($formation, $module->getFormation());
        self::assertCount(1, $formation->getModules());
    }

    public function testRemoveModuleDetachesIt(): void
    {
        $formation = new Formation();
        $module = (new Module())->setTitle('A')->setSlug('a');
        $formation->addModule($module);

        $formation->removeModule($module);

        self::assertNull($module->getFormation());
        self::assertCount(0, $formation->getModules());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/ModuleTest.php
```

Expected: 3 failing tests with `Error: Class "App\Entity\Module" not found`.

- [ ] **Step 3: Create the placeholder repository so the entity is wireable**

Create `src/Repository/ModuleRepository.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
final class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }
}
```

- [ ] **Step 4: Create the `Module` entity**

Create `src/Entity/Module.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module')]
#[ORM\UniqueConstraint(name: 'UNIQ_module_formation_slug', columns: ['formation_id', 'slug'])]
#[ORM\HasLifecycleCallbacks]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 180)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

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

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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
php bin/phpunit tests/Entity/ModuleTest.php tests/Entity/FormationTest.php
```

Expected: All Module tests pass (3 tests) and the new `testGetModulesReturnsEmptyCollectionOnConstruct` in FormationTest also passes. No previous FormationTest case regresses.

- [ ] **Step 6: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Module.php src/Repository/ModuleRepository.php tests/Entity/ModuleTest.php src/Entity/Formation.php tests/Entity/FormationTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(module): entity + Formation OneToMany relation"
```

---

### Task 3: Add `ModuleRepository::findByFormationOrdered()` under TDD

**Files:**
- Create: `app/tests/Repository/ModuleRepositoryTest.php`
- Modify: `app/src/Repository/ModuleRepository.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/Repository/ModuleRepositoryTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Module;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ModuleRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ModuleRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(ModuleRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindByFormationOrderedReturnsModulesSortedByDisplayOrder(): void
    {
        $formation = (new Formation())->setSlug('claude')->setTitle('Claude');
        $second = (new Module())->setTitle('Second')->setSlug('second')->setDisplayOrder(2);
        $first = (new Module())->setTitle('First')->setSlug('first')->setDisplayOrder(1);
        $third = (new Module())->setTitle('Third')->setSlug('third')->setDisplayOrder(3);

        $formation->addModule($second);
        $formation->addModule($first);
        $formation->addModule($third);

        $this->em->persist($formation);
        $this->em->flush();

        $result = $this->repo->findByFormationOrdered($formation);

        self::assertCount(3, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
        self::assertSame('third', $result[2]->getSlug());
    }

    public function testFindByFormationOrderedReturnsEmptyArrayWhenFormationHasNoModules(): void
    {
        $formation = (new Formation())->setSlug('empty')->setTitle('Empty');
        $this->em->persist($formation);
        $this->em->flush();

        self::assertSame([], $this->repo->findByFormationOrdered($formation));
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/ModuleRepositoryTest.php
```

Expected: 2 failing tests with `Error: Call to undefined method App\Repository\ModuleRepository::findByFormationOrdered()`.

- [ ] **Step 3: Implement `findByFormationOrdered`**

Open `src/Repository/ModuleRepository.php` and replace its content with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
final class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * @return Module[]
     */
    public function findByFormationOrdered(Formation $formation): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/ModuleRepositoryTest.php
```

Expected: 2 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/ModuleRepository.php tests/Repository/ModuleRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(module): repository findByFormationOrdered"
```

---

### Task 4: Generate and apply the `module` table migration

**Files:**
- Create: `app/migrations/Version<TIMESTAMP>.php` (timestamp auto-generated)

- [ ] **Step 1: Generate the migration**

Run:

```
php bin/console make:migration
```

Expected: console reports `created: migrations/VersionYYYYMMDDHHMMSS.php` (one new file).

- [ ] **Step 2: Verify the migration contains the `module` table**

Run:

```
LATEST=$(ls -t migrations/ | head -1) && grep -E 'CREATE TABLE module|formation_id|UNIQ_module_formation_slug' migrations/$LATEST
```

Expected: at least these three matches appear, including the `CREATE TABLE module (...)` statement and the unique index on `(formation_id, slug)`.

- [ ] **Step 3: Apply the migration against the dev MAMP database**

Run:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully migrated to version: DoctrineMigrations\Version...`.

- [ ] **Step 4: Verify the table in MAMP**

Run:

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech -e "DESCRIBE module;"
```

Expected: a row per column including `formation_id` as a foreign key, `slug`, `display_order`, `created_at`, `updated_at`.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add migrations/
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(module): add migration for module table"
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

Expected: All tests pass (Formation 5, Module 3, FormationRepo 3, ModuleRepo 2 → 13 tests minimum). Zero failures, zero errors.

- [ ] **Step 2: Run `doctrine:schema:validate`**

Run:

```
php bin/console doctrine:schema:validate
```

Expected: `[Mapping] OK - The mapping files are correct.` and `[Database] OK - The database schema is in sync with the mapping files.`

- [ ] **Step 3: Tag the milestone**

Run:

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit --allow-empty -m "milestone: Phase 1.3 Module entity complete"
```

---

## Self-Review

**Spec coverage:** Module entity columns (id, formation FK, title, slug, description, displayOrder, timestamps) are covered in Task 2. `Formation.modules` Collection + add/remove methods are covered in Task 1. `findByFormationOrdered` is covered in Task 3. Migration in Task 4. Full-suite check in Task 5. Cascade `persist` + `remove` + `orphanRemoval=true` are wired in Task 1 Step 3.

**Placeholder scan:** No `TODO`, no "implement later", no "similar to Task N", no orphan references. Every code block is complete.

**Type consistency:** `Module` is the only referenced class. `Formation::addModule(Module $module)` and `Formation::removeModule(Module $module)` match the calls in `ModuleTest`. `findByFormationOrdered(Formation $formation): array` signature matches the call site in `ModuleRepositoryTest`. `displayOrder` stays an `int` everywhere.

---

## Execution Handoff

Plan complete and saved to `app/docs/superpowers/plans/2026-05-30-module-entity.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
