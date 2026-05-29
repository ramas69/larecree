# Enrollment Entity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `Enrollment` Doctrine entity (a `User` inscribed in a `Formation`), an `EnrollmentSource` enum (`Stripe` / `Vip` / `Admin`), inverse OneToMany collections on `User` and `Formation`, a unique pair constraint on `(user_id, formation_id)`, helpers `isPaid()` / `isVipGranted()`, and two repository finders — `findByUser(User)` and `findOneByUserAndFormation(User, Formation)` — all under TDD.

**Architecture:** Standard Doctrine ORM. `Enrollment` is the owning side of two ManyToOne relations (to `User` and `Formation`). Both sides are required (`nullable: false`). Unique constraint `(user_id, formation_id)` enforces "a user only enrolls once in a given formation". `Enrollment.source` is a PHP 8.1 backed enum stored as a string column. `stripeSessionId`, `stripePaymentIntentId`, and `amountCents` are nullable so VIP/Admin grants stay valid without payment metadata. Tests boot the kernel against the in-memory SQLite test database.

**Tech Stack:** Symfony 7.4, Doctrine ORM 3.x, PHPUnit 11, PHP 8.5, MySQL 8 (dev), SQLite (test).

---

## File Structure

**Created in this plan:**
- `src/Entity/Enrollment.php` — Enrollment entity (id, user FK, formation FK, source enum, stripeSessionId, stripePaymentIntentId, amountCents, createdAt)
- `src/Entity/EnrollmentSource.php` — PHP 8.1 backed enum (`Stripe`, `Vip`, `Admin`)
- `src/Repository/EnrollmentRepository.php` — `findByUser()` + `findOneByUserAndFormation()`
- `tests/Entity/EnrollmentSourceTest.php` — enum cases coverage
- `tests/Entity/EnrollmentTest.php` — Enrollment unit tests including helpers
- `tests/Repository/EnrollmentRepositoryTest.php` — kernel integration test (finders + unique constraint)
- `migrations/Version<TIMESTAMP>.php` — Doctrine migration for `enrollment` table

**Modified:**
- `src/Entity/User.php` — add `$enrollments` Collection + `addEnrollment()` / `removeEnrollment()` / `getEnrollments()` + `OrderBy(['createdAt' => 'DESC'])`
- `src/Entity/Formation.php` — add `$enrollments` Collection + `addEnrollment()` / `removeEnrollment()` / `getEnrollments()` + `OrderBy(['createdAt' => 'DESC'])`
- `tests/Entity/FormationTest.php` — add test for the new `getEnrollments()` collection initial state
- `tests/Entity/UserTest.php` — first test file for the existing `User` class; covers the new `getEnrollments()` collection

---

### Task 1: Create the `EnrollmentSource` enum under TDD

**Files:**
- Create: `app/tests/Entity/EnrollmentSourceTest.php`
- Create: `app/src/Entity/EnrollmentSource.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/EnrollmentSourceTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\EnrollmentSource;
use PHPUnit\Framework\TestCase;

final class EnrollmentSourceTest extends TestCase
{
    public function testEnumHasStripeVipAdminCases(): void
    {
        self::assertSame('stripe', EnrollmentSource::Stripe->value);
        self::assertSame('vip', EnrollmentSource::Vip->value);
        self::assertSame('admin', EnrollmentSource::Admin->value);
    }

    public function testFromStringResolvesEnumCase(): void
    {
        self::assertSame(EnrollmentSource::Stripe, EnrollmentSource::from('stripe'));
        self::assertSame(EnrollmentSource::Vip, EnrollmentSource::from('vip'));
        self::assertSame(EnrollmentSource::Admin, EnrollmentSource::from('admin'));
    }

    public function testCasesReturnsExactlyThreeCases(): void
    {
        self::assertCount(3, EnrollmentSource::cases());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run from inside `app/`:

```
php bin/phpunit tests/Entity/EnrollmentSourceTest.php
```

Expected: 3 failing tests with `Error: Class "App\Entity\EnrollmentSource" not found`.

- [ ] **Step 3: Create the enum**

Create `src/Entity/EnrollmentSource.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

enum EnrollmentSource: string
{
    case Stripe = 'stripe';
    case Vip    = 'vip';
    case Admin  = 'admin';
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Entity/EnrollmentSourceTest.php
```

Expected: 3 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/EnrollmentSource.php tests/Entity/EnrollmentSourceTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(enrollment): EnrollmentSource enum (Stripe/Vip/Admin)"
```

---

### Task 2: Make `User` ready to own a `Collection<Enrollment>`

**Files:**
- Create: `app/tests/Entity/UserTest.php`
- Modify: `app/src/Entity/User.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Entity/UserTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetEnrollmentsReturnsEmptyCollectionOnConstruct(): void
    {
        $user = new User();

        self::assertCount(0, $user->getEnrollments());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getEnrollments());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/UserTest.php
```

Expected: 1 failing test with `Error: Call to undefined method App\Entity\User::getEnrollments()`.

- [ ] **Step 3: Initialize the `enrollments` Collection on `User`**

In `src/Entity/User.php`, add these imports at the top of the `use` block (right after the existing `use Symfony\Component\Security\Core\User\UserInterface;` line):

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
```

Add this property right after the existing `$updatedAt` property (`?\DateTimeImmutable $updatedAt = null;`):

```php
    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $enrollments;
```

Replace the existing constructor (the `__construct` that only sets `createdAt`) with this version:

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->enrollments = new ArrayCollection();
    }
```

Add these three methods at the end of the class (before the closing `}`):

```php
    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setUser($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment) && $enrollment->getUser() === $this) {
            $enrollment->setUser(null);
        }

        return $this;
    }
```

- [ ] **Step 4: The test still fails — `App\Entity\Enrollment` does not exist yet**

Run:

```
php bin/phpunit tests/Entity/UserTest.php
```

Expected: `Error: Class "App\Entity\Enrollment" not found`. This is intentional — Task 4 will introduce `Enrollment`. Do not commit yet.

---

### Task 3: Make `Formation` ready to own a `Collection<Enrollment>`

**Files:**
- Modify: `app/tests/Entity/FormationTest.php`
- Modify: `app/src/Entity/Formation.php`

- [ ] **Step 1: Add the failing test**

Open `tests/Entity/FormationTest.php` and append this test inside the class (just before the closing `}`):

```php
    public function testGetEnrollmentsReturnsEmptyCollectionOnConstruct(): void
    {
        $formation = new Formation();

        self::assertCount(0, $formation->getEnrollments());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $formation->getEnrollments());
    }
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: 1 failing test with `Error: Call to undefined method App\Entity\Formation::getEnrollments()`.

- [ ] **Step 3: Initialize the `enrollments` Collection on `Formation`**

`Formation` already imports `ArrayCollection` and `Collection` from Task 1.2.

Add this property right after the existing `$modules` property declaration (after `private Collection $modules;`):

```php
    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'formation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $enrollments;
```

Update the constructor — replace its body with this exact code (keep the existing `$modules` init, add `$enrollments`):

```php
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modules = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
    }
```

Add these three methods at the very end of the class (before the closing `}`):

```php
    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setFormation($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment) && $enrollment->getFormation() === $this) {
            $enrollment->setFormation(null);
        }

        return $this;
    }
```

- [ ] **Step 4: The test still fails — `App\Entity\Enrollment` does not exist yet**

Run:

```
php bin/phpunit tests/Entity/FormationTest.php
```

Expected: `Error: Class "App\Entity\Enrollment" not found`. This is intentional — Task 4 will introduce `Enrollment`. Do not commit yet.

---

### Task 4: Create the `Enrollment` entity under TDD

**Files:**
- Create: `app/tests/Entity/EnrollmentTest.php`
- Create: `app/src/Entity/Enrollment.php`
- Create: `app/src/Repository/EnrollmentRepository.php`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Entity/EnrollmentTest.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class EnrollmentTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $enrollment = new Enrollment();

        self::assertNull($enrollment->getId());
        self::assertNull($enrollment->getUser());
        self::assertNull($enrollment->getFormation());
        self::assertNull($enrollment->getSource());
        self::assertNull($enrollment->getStripeSessionId());
        self::assertNull($enrollment->getStripePaymentIntentId());
        self::assertNull($enrollment->getAmountCents());
        self::assertInstanceOf(\DateTimeImmutable::class, $enrollment->getCreatedAt());
    }

    public function testAddingEnrollmentToUserBindsBothSides(): void
    {
        $user = new User();
        $enrollment = (new Enrollment())->setSource(EnrollmentSource::Stripe);

        $user->addEnrollment($enrollment);

        self::assertSame($user, $enrollment->getUser());
        self::assertCount(1, $user->getEnrollments());
    }

    public function testAddingEnrollmentToFormationBindsBothSides(): void
    {
        $formation = new Formation();
        $enrollment = (new Enrollment())->setSource(EnrollmentSource::Stripe);

        $formation->addEnrollment($enrollment);

        self::assertSame($formation, $enrollment->getFormation());
        self::assertCount(1, $formation->getEnrollments());
    }

    public function testIsPaidIsTrueOnlyForStripeSource(): void
    {
        $stripe = (new Enrollment())->setSource(EnrollmentSource::Stripe);
        $vip    = (new Enrollment())->setSource(EnrollmentSource::Vip);
        $admin  = (new Enrollment())->setSource(EnrollmentSource::Admin);

        self::assertTrue($stripe->isPaid());
        self::assertFalse($vip->isPaid());
        self::assertFalse($admin->isPaid());
    }

    public function testIsVipGrantedIsTrueOnlyForVipSource(): void
    {
        $vip    = (new Enrollment())->setSource(EnrollmentSource::Vip);
        $stripe = (new Enrollment())->setSource(EnrollmentSource::Stripe);
        $admin  = (new Enrollment())->setSource(EnrollmentSource::Admin);

        self::assertTrue($vip->isVipGranted());
        self::assertFalse($stripe->isVipGranted());
        self::assertFalse($admin->isVipGranted());
    }

    public function testIsPaidAndIsVipGrantedAreFalseWhenSourceIsNull(): void
    {
        $enrollment = new Enrollment();

        self::assertFalse($enrollment->isPaid());
        self::assertFalse($enrollment->isVipGranted());
    }

    public function testAmountCentsRoundTrips(): void
    {
        $enrollment = (new Enrollment())->setAmountCents(39700);

        self::assertSame(39700, $enrollment->getAmountCents());
    }
}
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Entity/EnrollmentTest.php
```

Expected: 7 failing tests with `Error: Class "App\Entity\Enrollment" not found`.

- [ ] **Step 3: Create the placeholder repository**

Create `src/Repository/EnrollmentRepository.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
final class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }
}
```

- [ ] **Step 4: Create the `Enrollment` entity**

Create `src/Entity/Enrollment.php` with this exact content:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment')]
#[ORM\UniqueConstraint(name: 'UNIQ_enrollment_user_formation', columns: ['user_id', 'formation_id'])]
#[ORM\HasLifecycleCallbacks]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(length: 16, enumType: EnrollmentSource::class)]
    private ?EnrollmentSource $source = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $amountCents = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
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

    public function getSource(): ?EnrollmentSource
    {
        return $this->source;
    }

    public function setSource(?EnrollmentSource $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function setAmountCents(?int $amountCents): static
    {
        $this->amountCents = $amountCents;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPaid(): bool
    {
        return $this->source === EnrollmentSource::Stripe;
    }

    public function isVipGranted(): bool
    {
        return $this->source === EnrollmentSource::Vip;
    }
}
```

- [ ] **Step 5: Run all entity tests and verify they pass**

Run:

```
php bin/phpunit tests/Entity/EnrollmentTest.php tests/Entity/UserTest.php tests/Entity/FormationTest.php tests/Entity/EnrollmentSourceTest.php
```

Expected: 7 Enrollment tests + 1 User test + 6 Formation tests (5 existing + the new collection test) + 3 EnrollmentSource tests. No previous test regresses.

- [ ] **Step 6: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Entity/Enrollment.php src/Repository/EnrollmentRepository.php tests/Entity/EnrollmentTest.php src/Entity/User.php src/Entity/Formation.php tests/Entity/UserTest.php tests/Entity/FormationTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(enrollment): entity + User/Formation collections + isPaid/isVipGranted helpers"
```

---

### Task 5: Add repository finders + unique pair constraint integration test

**Files:**
- Create: `app/tests/Repository/EnrollmentRepositoryTest.php`
- Modify: `app/src/Repository/EnrollmentRepository.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/Repository/EnrollmentRepositoryTest.php` with this exact content:

```php
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
        usleep(10_000); // ensure newer createdAt > older createdAt at second precision
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
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```
php bin/phpunit tests/Repository/EnrollmentRepositoryTest.php
```

Expected: 3 failing tests. The first two fail with `Error: Call to undefined method App\Repository\EnrollmentRepository::findByUser()` and `findOneByUserAndFormation()` (raised via Doctrine `InvalidMagicMethodCall`). The third test (duplicate constraint) fails because the second `flush()` does not yet raise — the unique constraint exists at the entity level via the `#[ORM\UniqueConstraint]` from Task 4, and SQLite enforces it, so this test should fail with `findOneByUserAndFormation` missing as a transitive consequence.

- [ ] **Step 3: Implement both finders**

Open `src/Repository/EnrollmentRepository.php` and replace its content with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
final class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    /**
     * @return Enrollment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndFormation(User $user, Formation $formation): ?Enrollment
    {
        return $this->findOneBy([
            'user' => $user,
            'formation' => $formation,
        ]);
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

Run:

```
php bin/phpunit tests/Repository/EnrollmentRepositoryTest.php
```

Expected: 3 tests OK.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add src/Repository/EnrollmentRepository.php tests/Repository/EnrollmentRepositoryTest.php
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(enrollment): repository findByUser + findOneByUserAndFormation + unique pair test"
```

---

### Task 6: Generate and apply the `enrollment` table migration

**Files:**
- Create: `app/migrations/Version<TIMESTAMP>.php` (timestamp auto-generated)

- [ ] **Step 1: Generate the migration**

Run:

```
php bin/console make:migration
```

Expected: console reports `created: migrations/VersionYYYYMMDDHHMMSS.php` (one new file).

- [ ] **Step 2: Verify the migration contains the `enrollment` table**

Run:

```
LATEST=$(ls -t migrations/ | head -1) && grep -E 'CREATE TABLE enrollment|user_id|formation_id|UNIQ_enrollment_user_formation|stripe_session_id|amount_cents' migrations/$LATEST
```

Expected: matches appear, including `CREATE TABLE enrollment (...)`, `user_id`, `formation_id`, `UNIQ_enrollment_user_formation`, `stripe_session_id`, and `amount_cents`.

- [ ] **Step 3: Apply the migration against the dev MAMP database**

Run:

```
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully migrated to version: DoctrineMigrations\Version...`.

- [ ] **Step 4: Verify the table in MAMP**

Run:

```
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech -e "DESCRIBE enrollment;"
```

Expected: one row per column including `user_id`, `formation_id`, `source`, `stripe_session_id`, `stripe_payment_intent_id`, `amount_cents`, `created_at`.

- [ ] **Step 5: Commit**

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app add migrations/
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit -m "feat(enrollment): add migration for enrollment table"
```

---

### Task 7: Final full-suite verification + milestone

**Files:**
- None modified.

- [ ] **Step 1: Run the entire PHPUnit suite**

Run:

```
php bin/phpunit
```

Expected: All tests pass. With Enrollment added: previous 35 + Enrollment 7 + EnrollmentSource 3 + User 1 + Formation +1 + EnrollmentRepo 3 → 50 tests minimum. Zero failures, zero errors.

- [ ] **Step 2: Run `doctrine:schema:validate`**

Run:

```
php bin/console doctrine:schema:validate
```

Expected: `[Mapping] OK - The mapping files are correct.` and `[Database] OK - The database schema is in sync with the mapping files.`

- [ ] **Step 3: Tag the milestone**

Run:

```
git -C /Users/soumare/Desktop/Perso/larecreetech/app commit --allow-empty -m "milestone: Phase 1.6 Enrollment entity complete"
```

---

## Self-Review

**Spec coverage:** Enrollment entity columns (id, user FK, formation FK, source enum, stripeSessionId, stripePaymentIntentId, amountCents, createdAt) are covered in Task 4. `EnrollmentSource` enum (Stripe/Vip/Admin) is covered in Task 1. `User.enrollments` Collection covered in Task 2. `Formation.enrollments` Collection covered in Task 3. `isPaid()` + `isVipGranted()` helpers covered by 3 dedicated tests in Task 4. Unique `(user_id, formation_id)` constraint covered both at mapping level (Task 4 `#[ORM\UniqueConstraint]`) and at runtime (Task 5 third test asserting `UniqueConstraintViolationException`). Repository finders `findByUser()` + `findOneByUserAndFormation()` covered in Task 5. Migration in Task 6. Cascade `persist` + `remove` + `orphanRemoval=true` wired on both inverse sides.

**Placeholder scan:** No `TODO`, no "implement later", no "similar to Task N", no orphan references. Every code block is complete and copy-pasteable.

**Type consistency:** `Enrollment` is the only referenced entity class. `User::addEnrollment(Enrollment $enrollment)` and `Formation::addEnrollment(Enrollment $enrollment)` match the calls in `EnrollmentTest`. `findByUser(User $user): array` and `findOneByUserAndFormation(User $user, Formation $formation): ?Enrollment` signatures match every call site. `EnrollmentSource::Stripe`, `Vip`, `Admin` cases are used identically across Tasks 1, 4, and 5. `amountCents` stays `?int` everywhere (nullable for non-Stripe sources).

---

## Execution Handoff

Plan complete and saved to `app/docs/superpowers/plans/2026-05-30-enrollment-entity.md`. Two execution options:

1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
