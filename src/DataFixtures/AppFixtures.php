<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->makeUser('admin@larecreetech.com', 'admin', 'Admin', 'Rama', ['ROLE_ADMIN']);
        $vip   = $this->makeUser('vip@larecreetech.com', 'vip', 'VIP', 'Test', ['ROLE_VIP']);
        $rama  = $this->makeUser('rama@hallia.ai', 'rama', 'Rama', 'Soumare', ['ROLE_STUDENT']);

        $manager->persist($admin);
        $manager->persist($vip);
        $manager->persist($rama);

        [$claude, $claudeLessons] = $this->seedClaudeFormation($manager);
        [$design, $designLessons] = $this->seedDesignFormation($manager);

        // Rama : enrollment Stripe sur Claude + progression M01+M02 done, M03L1 done, M03L2 47 %
        $ramaClaude = $this->enroll($manager, $rama, $claude, EnrollmentSource::Stripe, 39700, 'cs_test_claude', 'pi_test_claude');
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[0]);
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[1]);
        $this->markLessonCompleted($manager, $ramaClaude, $claudeLessons[2][0]);
        $this->recordPartialWatch($manager, $ramaClaude, $claudeLessons[2][1], 47);

        // VIP : enrollment Vip gratuit sur Claude
        $this->enroll($manager, $vip, $claude, EnrollmentSource::Vip);

        // Rama : enrollment Stripe sur Design + M01 done, M02L1 30 %
        $ramaDesign = $this->enroll($manager, $rama, $design, EnrollmentSource::Stripe, 29700, 'cs_test_design', 'pi_test_design');
        $this->markModuleCompleted($manager, $ramaDesign, $designLessons[0]);
        $this->recordPartialWatch($manager, $ramaDesign, $designLessons[1][0], 30);

        $manager->flush();
    }

    /**
     * @return array{0: Formation, 1: array<int, array<int, Lesson>>}
     */
    private function seedClaudeFormation(ObjectManager $manager): array
    {
        $claude = (new Formation())
            ->setSlug('claude-2026')
            ->setTitle('Formation Claude 2026')
            ->setSubtitle('Maîtriser Claude pour ton métier')
            ->setDescription('De zéro à expert·e Claude en 8 modules pratiques.')
            ->setPriceCents(39700)
            ->setCurrency('EUR')
            ->setPublished(true)
            ->setDisplayOrder(1);
        $manager->persist($claude);

        $modulesSpec = [
            ['Premiers pas avec Claude',           'premiers-pas'],
            ['Le prompt parfait',                  'prompt-parfait'],
            ['Configurer un Project comme un·e pro', 'projects'],
            ['Documents, fichiers, contexte long', 'documents-contexte'],
            ['Artifacts et code',                  'artifacts-code'],
            ['Workflows quotidiens',               'workflows-quotidiens'],
            ['Claude pour ton métier',             'claude-metier'],
            ['Finale : ton agent personnel',       'agent-personnel'],
        ];

        return [$claude, $this->seedModules($manager, $claude, $modulesSpec, lessonsPerModule: 4, vimeoPrefix: '9999')];
    }

    /**
     * @return array{0: Formation, 1: array<int, array<int, Lesson>>}
     */
    private function seedDesignFormation(ObjectManager $manager): array
    {
        $design = (new Formation())
            ->setSlug('design-web-2026')
            ->setTitle('Design Web 2026')
            ->setSubtitle('De Figma à la prod sans drama')
            ->setDescription('Atelier express en 4 modules pour designer avec rigueur produit.')
            ->setPriceCents(29700)
            ->setCurrency('EUR')
            ->setPublished(true)
            ->setDisplayOrder(2);
        $manager->persist($design);

        $modulesSpec = [
            ['Fondamentaux visuels',       'fondamentaux-visuels'],
            ['Système de design',          'systeme-design'],
            ['Composants & Storybook',     'composants-storybook'],
            ['Finale : portfolio produit', 'portfolio-produit'],
        ];

        return [$design, $this->seedModules($manager, $design, $modulesSpec, lessonsPerModule: 3, vimeoPrefix: '8888')];
    }

    /**
     * @param list<array{0: string, 1: string}> $modulesSpec
     * @return array<int, array<int, Lesson>> indexed by module index, value = list of lessons
     */
    private function seedModules(ObjectManager $manager, Formation $formation, array $modulesSpec, int $lessonsPerModule, string $vimeoPrefix): array
    {
        $lessonsByModule = [];
        foreach ($modulesSpec as $i => [$title, $slug]) {
            $module = (new Module())
                ->setSlug($slug)
                ->setTitle($title)
                ->setDescription('Module '.($i + 1).' — '.$title)
                ->setDisplayOrder($i + 1);
            $formation->addModule($module);
            $manager->persist($module);

            $lessons = [];
            for ($li = 1; $li <= $lessonsPerModule; $li++) {
                $lesson = (new Lesson())
                    ->setSlug('m'.($i + 1).'-l'.$li)
                    ->setTitle('Leçon '.$li.' — '.$module->getTitle())
                    ->setVimeoVideoId($vimeoPrefix.($i + 1).$li)
                    ->setDescription('Contenu pédagogique de la leçon '.$li.'.')
                    ->setDurationSeconds(60 * (8 + $li * 2))
                    ->setDisplayOrder($li);
                $module->addLesson($lesson);
                $manager->persist($lesson);
                $lessons[] = $lesson;
            }
            $lessonsByModule[$i] = $lessons;
        }

        return $lessonsByModule;
    }

    private function enroll(
        ObjectManager $manager,
        User $user,
        Formation $formation,
        EnrollmentSource $source,
        ?int $amountCents = null,
        ?string $stripeSessionId = null,
        ?string $stripePaymentIntentId = null,
    ): Enrollment {
        $enrollment = (new Enrollment())
            ->setUser($user)
            ->setFormation($formation)
            ->setSource($source)
            ->setAmountCents($amountCents)
            ->setStripeSessionId($stripeSessionId)
            ->setStripePaymentIntentId($stripePaymentIntentId);
        $manager->persist($enrollment);

        return $enrollment;
    }

    /**
     * @param list<Lesson> $lessons
     */
    private function markModuleCompleted(ObjectManager $manager, Enrollment $enrollment, array $lessons): void
    {
        foreach ($lessons as $lesson) {
            $this->markLessonCompleted($manager, $enrollment, $lesson);
        }
    }

    private function markLessonCompleted(ObjectManager $manager, Enrollment $enrollment, Lesson $lesson): void
    {
        $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
        $progress->recordWatch($lesson->getDurationSeconds(), 100);
        $progress->markCompleted();
        $manager->persist($progress);
    }

    private function recordPartialWatch(ObjectManager $manager, Enrollment $enrollment, Lesson $lesson, int $percent): void
    {
        $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
        $progress->recordWatch((int) round($lesson->getDurationSeconds() * $percent / 100), $percent);
        $manager->persist($progress);
    }

    /**
     * @param string[] $roles
     */
    private function makeUser(string $email, string $plainPassword, string $firstName, string $lastName, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

        return $user;
    }
}
