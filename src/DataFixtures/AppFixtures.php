<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\Resource;
use App\Entity\ResourceType;
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
        $this->seedManusFormation($manager);

        // Rama : enrollment Stripe sur Claude + progression M01+M02 done, M03L1 done, M03L2 47 %
        $ramaClaude = $this->enroll($manager, $rama, $claude, EnrollmentSource::Stripe, 39700, 'cs_test_claude', 'pi_test_claude');
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[0]);
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[1]);
        $this->markLessonCompleted($manager, $ramaClaude, $claudeLessons[2][0]);
        $this->recordPartialWatch($manager, $ramaClaude, $claudeLessons[2][1], 47);

        // VIP : enrollment Vip gratuit sur Claude
        $this->enroll($manager, $vip, $claude, EnrollmentSource::Vip);

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
            ['Démarrer avec Claude',                 'demarrer',             'La fondation — tu ne peux rien construire dessus sans ça.'],
            ['Le prompt parfait',                    'prompt-parfait',       'La compétence qui sépare ceux qui galèrent de ceux qui pilotent.'],
            ['Projects : ton bureau Claude',         'projects',             'Arrête de tout réexpliquer à Claude à chaque conversation.'],
            ['Artifacts : créer des choses',         'artifacts',            'Documents, code, visuels — Claude livre, pas juste discute.'],
            ['Cowork : Skills & Plugins',            'skills-plugins',       'Le game changer non-dev de 2026.'],
            ['Cowork : Connectors & Routines',       'connectors-routines',  'Claude bosse pendant que tu dors.'],
            ['Claude Design',                        'claude-design',        'Tes visuels en chattant. Tu vas être en avance.'],
            ['Combiner tout pour ton métier',        'combiner-metier',      'La synthèse. Ce qui transforme une compétence en business.'],
        ];

        return [$claude, $this->seedModules($manager, $claude, $modulesSpec, lessonsPerModule: 4, vimeoPrefix: '9999')];
    }

    private function seedManusFormation(ObjectManager $manager): Formation
    {
        $manus = (new Formation())
            ->setSlug('manus-2026')
            ->setTitle('Formation Manus 2026')
            ->setSubtitle('L\'agent IA qui exécute pendant que tu fais autre chose.')
            ->setDescription('Découvre Manus, le co-pilote autonome qui transforme tes intentions en livrables. Bientôt disponible.')
            ->setPriceCents(34700)
            ->setCurrency('EUR')
            ->setPublished(true)
            ->setComingSoon(true)
            ->setDisplayOrder(2);
        $manager->persist($manus);

        $modulesSpec = [
            ['Découvrir Manus',          'decouvrir-manus',       'Comprendre l\'agent : ce qu\'il fait vraiment, ce qu\'il ne fait pas.'],
            ['Brief & garde-fous',       'brief-garde-fous',      'Lui parler comme à un junior brillant qu\'on ne supervise pas.'],
            ['Automatisations métier',   'automatisations-metier','Plug Manus dans tes outils — il livre, tu valides.'],
            ['Finale : ton bras droit',  'bras-droit',            'Ton workflow où Manus devient un membre de ton équipe.'],
        ];

        $this->seedModules($manager, $manus, $modulesSpec, lessonsPerModule: 3, vimeoPrefix: '7777');

        return $manus;
    }

    /**
     * @param list<array{0: string, 1: string, 2?: string}> $modulesSpec
     * @return array<int, array<int, Lesson>> indexed by module index, value = list of lessons
     */
    private function seedModules(ObjectManager $manager, Formation $formation, array $modulesSpec, int $lessonsPerModule, string $vimeoPrefix): array
    {
        $lessonsByModule = [];
        foreach ($modulesSpec as $i => $spec) {
            [$title, $slug] = $spec;
            $description = $spec[2] ?? 'Module '.($i + 1).' — '.$title;
            $module = (new Module())
                ->setSlug($slug)
                ->setTitle($title)
                ->setDescription($description)
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
                $this->seedLessonResources($manager, $lesson, $i + 1, $li);
                $lessons[] = $lesson;
            }
            $lessonsByModule[$i] = $lessons;
        }

        return $lessonsByModule;
    }

    private function seedLessonResources(ObjectManager $manager, Lesson $lesson, int $moduleIdx, int $lessonIdx): void
    {
        $pdf = (new Resource())
            ->setLesson($lesson)
            ->setType(ResourceType::File)
            ->setTitle('Fiche récap — Leçon '.$lessonIdx)
            ->setFilePath('/files/lessons/m'.$moduleIdx.'-l'.$lessonIdx.'.pdf')
            ->setDisplayOrder(1);
        $manager->persist($pdf);

        $link = (new Resource())
            ->setLesson($lesson)
            ->setType(ResourceType::Link)
            ->setTitle('Lien utile — Aller plus loin')
            ->setUrl('https://docs.anthropic.com/')
            ->setDisplayOrder(2);
        $manager->persist($link);
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
