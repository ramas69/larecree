# Formation Entity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `Formation` Doctrine entity (e-learning course top-level container), its repository helpers (`findPublished`, `findBySlug`), and a price formatting helper, all under TDD.

**Architecture:** Standard Symfony Doctrine ORM entity with PHP 8 attributes. Slug auto-derived from title on persist via Doctrine lifecycle callback. Price stored as integer cents (no floats). Helper formats cents to localized euro string. Repository extends `ServiceEntityRepository` with two custom finders. Tests use Symfony's PHPUnit kernel with an in-memory SQLite database, isolated via the standard `KernelTestCase` pattern.

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, PHPUnit 11, PHP 8.5, MAMP MySQL 8 (dev), SQLite (test).

---

## File Structure

**Created in this plan:**
- `src/Entity/Formation.php` — Formation entity (id, slug, title, subtitle, description, priceCents, currency, coverImage, vimeoFolderId, published, displayOrder, timestamps)
- `src/Repository/FormationRepository.php` — `findPublished()`, `findBySlug()`
- `tests/Entity/FormationTest.php` — Unit tests for entity behaviour
- `tests/Repository/FormationRepositoryTest.php` — Integration tests with kernel + DB
- `migrations/VersionYYYYMMDDHHMMSS.php` — Doctrine migration for `formation` table

**Modified:**
- `composer.json` — none (PHPUnit already present via webapp pack)
- `phpunit.dist.xml` — none (default already wired)
- `config/packages/doctrine.yaml` — none (default config sufficient)
- `.env.test` — set `DATABASE_URL="sqlite:///:memory:"` if not already

**Conventions used:**
- Strict types declared on every PHP file
- `final` on the entity (Doctrine ORM 3 supports this when no inheritance)
- PHP 8 attributes for all Doctrine mapping
- Snake-case table/column names (Doctrine default)
- Method signatures return `static` for fluent setters

---

### Task 1: Test database configuration

**Files:**
- Modify: `app/.env.test`

- [ ] **Step 1: Set the test database to in-memory SQLite**

Open `app/.env.test` and ensure it contains a `DATABASE_URL` override. If the file already exists, append or replace the existing `DATABASE_URL`. The final content of `.env.test` must include the line:

```
DATABASE_URL="sqlite:///:memory:"
```

If the file already exists with other content, keep that content and only add or replace the `DATABASE_URL` line.

- [ ] **Step 2: Verify configuration is picked up**

Run from inside `app/`:

```
APP_ENV=test php bin/console debug:config doctrine dbal.url
```

Expected output: a line printing `'sqlite:///:memory:'` as the resolved URL.

- [ ] **Step 3: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add .env.test
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "chore(test): use in-memory sqlite for tests"
```

---

### Task 2: Write the failing test for `Formation::__construct` and basic getters

**Files:**
- Create: `app/tests/Entity/FormationTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/FormationTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Formation;
use PHPUnit\Framework\TestCase;

final class FormationTest extends TestCase
{
    public function testConstructorSetsRequiredDefaults(): void
    {
        $formation = new Formation();

        self::assertNull($formation->getId());
        self::assertFalse($formation->isPublished());
        self::assertSame(0, $formation->getDisplayOrder());
        self::assertSame('EUR', $formation->getCurrency());
        self::assertInstanceOf(\DateTimeImmutable::class, $formation->getCreatedAt());
        self::assertNull($formation->getUpdatedAt());
    }

    public function testTitleAndSubtitleSetters(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Claude 2026');
        $formation->setSubtitle('La formation complète');

        self::assertSame('Formation Claude 2026', $formation->getTitle());
        self::assertSame('La formation complète', $formation->getSubtitle());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: FAIL with `Error: Class "App\Entity\Formation" not found`. If PHPUnit is not installed, install it first by running `composer require --dev symfony/test-pack`, then re-run.

- [ ] **Step 3: Create the minimal `Formation` entity to pass the test**

Create `src/Entity/Formation.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formation')]
#[ORM\HasLifecycleCallbacks]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $priceCents = 0;

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $vimeoFolderId = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;
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

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): static
    {
        $this->priceCents = $priceCents;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getVimeoFolderId(): ?string
    {
        return $this->vimeoFolderId;
    }

    public function setVimeoFolderId(?string $vimeoFolderId): static
    {
        $this->vimeoFolderId = $vimeoFolderId;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;
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

- [ ] **Step 4: Create a placeholder repository so the entity loads**

Create `src/Repository/FormationRepository.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
final class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }
}
```

- [ ] **Step 5: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: 2 tests OK.

- [ ] **Step 6: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Formation.php src/Repository/FormationRepository.php tests/Entity/FormationTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(formation): scaffold entity with required defaults"
```

---

### Task 3: Add `getPriceFormatted()` helper under TDD

**Files:**
- Modify: `app/tests/Entity/FormationTest.php`
- Modify: `app/src/Entity/Formation.php`

- [ ] **Step 1: Add the failing test for the formatted price helper**

Open `tests/Entity/FormationTest.php` and append this test inside the class (just before the closing `}`):

```php
    public function testGetPriceFormattedRendersEuroFromCents(): void
    {
        $formation = new Formation();
        $formation->setPriceCents(39700);

        self::assertSame('397,00 €', $formation->getPriceFormatted());
    }

    public function testGetPriceFormattedHandlesZero(): void
    {
        $formation = new Formation();

        self::assertSame('0,00 €', $formation->getPriceFormatted());
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: 2 tests failing with `Error: Call to undefined method App\Entity\Formation::getPriceFormatted()`.

- [ ] **Step 3: Implement `getPriceFormatted`**

In `src/Entity/Formation.php`, add this method right after `setPriceCents`:

```php
    public function getPriceFormatted(): string
    {
        $amount = $this->priceCents / 100;
        $formatted = number_format($amount, 2, ',', ' ');

        return $formatted.' '.($this->currency === 'EUR' ? '€' : $this->currency);
    }
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: 4 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Formation.php tests/Entity/FormationTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(formation): add getPriceFormatted helper"
```

---

### Task 4: Add `FormationRepository::findPublished()` under TDD

**Files:**
- Create: `app/tests/Repository/FormationRepositoryTest.php`
- Modify: `app/src/Repository/FormationRepository.php`

- [ ] **Step 1: Write the failing repository integration test**

Create `tests/Repository/FormationRepositoryTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FormationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormationRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(FormationRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testFindPublishedReturnsOnlyPublishedOrderedByDisplayOrder(): void
    {
        $draft = (new Formation())->setSlug('draft')->setTitle('Draft')->setPriceCents(0)->setDisplayOrder(1);
        $second = (new Formation())->setSlug('second')->setTitle('Second')->setPriceCents(10000)->setPublished(true)->setDisplayOrder(2);
        $first = (new Formation())->setSlug('first')->setTitle('First')->setPriceCents(10000)->setPublished(true)->setDisplayOrder(1);

        $this->em->persist($draft);
        $this->em->persist($second);
        $this->em->persist($first);
        $this->em->flush();

        $result = $this->repo->findPublished();

        self::assertCount(2, $result);
        self::assertSame('first', $result[0]->getSlug());
        self::assertSame('second', $result[1]->getSlug());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/FormationRepositoryTest.php
```

Expected: FAIL with `Error: Call to undefined method App\Repository\FormationRepository::findPublished()`.

- [ ] **Step 3: Implement `findPublished` in the repository**

Open `src/Repository/FormationRepository.php` and add this method inside the class (after the constructor):

```php
    /**
     * @return Formation[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.published = true')
            ->orderBy('f.displayOrder', 'ASC')
            ->addOrderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/FormationRepositoryTest.php
```

Expected: 1 test OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/FormationRepository.php tests/Repository/FormationRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(formation): repository findPublished ordered by displayOrder"
```

---

### Task 5: Add `FormationRepository::findBySlug()` under TDD

**Files:**
- Modify: `app/tests/Repository/FormationRepositoryTest.php`
- Modify: `app/src/Repository/FormationRepository.php`

- [ ] **Step 1: Add the failing test**

Open `tests/Repository/FormationRepositoryTest.php` and append this method inside the class (before the closing `}`):

```php
    public function testFindBySlugReturnsFormationWhenItExists(): void
    {
        $formation = (new Formation())
            ->setSlug('formation-claude')
            ->setTitle('Formation Claude')
            ->setPriceCents(39700)
            ->setPublished(true);

        $this->em->persist($formation);
        $this->em->flush();

        $loaded = $this->repo->findBySlug('formation-claude');

        self::assertNotNull($loaded);
        self::assertSame('Formation Claude', $loaded->getTitle());
    }

    public function testFindBySlugReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->repo->findBySlug('does-not-exist'));
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/FormationRepositoryTest.php
```

Expected: 2 new tests failing with `Error: Call to undefined method App\Repository\FormationRepository::findBySlug()`.

- [ ] **Step 3: Implement `findBySlug`**

Open `src/Repository/FormationRepository.php` and add this method after `findPublished`:

```php
    public function findBySlug(string $slug): ?Formation
    {
        return $this->findOneBy(['slug' => $slug]);
    }
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/FormationRepositoryTest.php
```

Expected: 3 tests OK in this file.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/FormationRepository.php tests/Repository/FormationRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(formation): repository findBySlug"
```

---

### Task 6: Generate and apply the `formation` table migration

**Files:**
- Create: `app/migrations/Version<TIMESTAMP>.php` (timestamp auto-generated)

- [ ] **Step 1: Generate the migration**

Run:

```
php bin/console make:migration
```

Expected: console reports `created: migrations/VersionYYYYMMDDHHMMSS.php` (one new file).

- [ ] **Step 2: Open the generated migration and confirm it contains the `formation` table**

Run:

```
ls -t migrations/ | head -1
```

Then open the printed file. Verify that the `up()` method contains a `CREATE TABLE formation (...)` statement with the columns `id`, `slug`, `title`, `subtitle`, `description`, `price_cents`, `currency`, `cover_image`, `vimeo_folder_id`, `published`, `display_order`, `created_at`, `updated_at`. If any column is missing, run `php bin/console doctrine:schema:validate` and resolve the diff.

- [ ] **Step 3: Apply the migration against the dev MAMP database**

Run:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected output ends with `[OK] Successfully migrated to version: DoctrineMigrations\Version...`.

- [ ] **Step 4: Verify the table in MAMP**

Run:

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech -e "DESCRIBE formation;"
```

Expected: 13 rows printed (one per column), with `slug` shown as `UNI`.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add migrations/
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(formation): add migration for formation table"
```

---

### Task 7: Final full-suite verification

**Files:**
- None modified.

- [ ] **Step 1: Run the entire PHPUnit suite**

Run:

```
php bin/phpunit
```

Expected: All tests pass. No failures, no errors, no warnings.

- [ ] **Step 2: Run `doctrine:schema:validate` to confirm DB is in sync with mappings**

Run:

```
php bin/console doctrine:schema:validate
```

Expected: `[Mapping] OK - The mapping files are correct.` and `[Database] OK - The database schema is in sync with the mapping files.`

- [ ] **Step 3: Tag the milestone with a commit annotation**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit --allow-empty -m "milestone: Phase 1.2 Formation entity complete"
```

---

## Self-Review

**Spec coverage:** Every column from PLAN.md Phase 1.2 (id, slug, title, subtitle, description, price cents, currency, coverImage, vimeoFolderId, published, displayOrder, createdAt, updatedAt) is mapped in Task 2. The two repository helpers (`findPublished`, `findBySlug`) are covered in Tasks 4 and 5. The `getPriceFormatted` helper is covered in Task 3.

**Placeholder scan:** No `TODO`, no "implement later", no "similar to Task N", no orphan references. Every code block is complete and copy-pasteable.

**Type consistency:** `getPriceFormatted()`, `findPublished()`, and `findBySlug()` keep identical names and signatures across the tests that call them and the implementations that define them. `priceCents` stays an `int` everywhere. `Formation` is the only referenced class.

---

## Execution Handoff

Plan complete and saved to `app/docs/superpowers/plans/2026-05-29-formation-entity.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
