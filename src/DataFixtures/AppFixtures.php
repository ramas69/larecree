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
        // Users
        $admin = $this->makeUser('admin@larecreetech.com', 'admin', 'Admin', 'Rama', ['ROLE_ADMIN']);
        $vip   = $this->makeUser('vip@larecreetech.com', 'vip', 'VIP', 'Test', ['ROLE_VIP']);
        $rama  = $this->makeUser('rama@hallia.ai', 'rama', 'Rama', 'Soumare', ['ROLE_STUDENT']);

        $manager->persist($admin);
        $manager->persist($vip);
        $manager->persist($rama);

        // Formation Claude — 8 modules
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

        $modules = [];
        foreach ($modulesSpec as $i => [$title, $slug]) {
            $mod = (new Module())
                ->setSlug($slug)
                ->setTitle($title)
                ->setDescription('Module '.($i + 1).' — '.$title)
                ->setDisplayOrder($i + 1);
            $claude->addModule($mod);
            $manager->persist($mod);
            $modules[] = $mod;
        }

        // 4 leçons par module
        $lessonsByModule = [];
        foreach ($modules as $mi => $mod) {
            $lessonsByModule[$mi] = [];
            for ($li = 1; $li <= 4; $li++) {
                $lesson = (new Lesson())
                    ->setSlug('m'.($mi + 1).'-l'.$li)
                    ->setTitle('Leçon '.$li.' — '.$mod->getTitle())
                    ->setVimeoVideoId('9999'.($mi + 1).$li)
                    ->setDescription('Contenu pédagogique de la leçon '.$li.'.')
                    ->setDurationSeconds(60 * (8 + $li * 2))
                    ->setDisplayOrder($li);
                $mod->addLesson($lesson);
                $manager->persist($lesson);
                $lessonsByModule[$mi][] = $lesson;
            }
        }

        // Enrollment Rama → Claude (Stripe)
        $enrollment = (new Enrollment())
            ->setUser($rama)
            ->setFormation($claude)
            ->setSource(EnrollmentSource::Stripe)
            ->setAmountCents(39700)
            ->setStripeSessionId('cs_test_demo')
            ->setStripePaymentIntentId('pi_test_demo');
        $manager->persist($enrollment);

        // Enrollment VIP → Claude (Vip)
        $vipEnrollment = (new Enrollment())
            ->setUser($vip)
            ->setFormation($claude)
            ->setSource(EnrollmentSource::Vip);
        $manager->persist($vipEnrollment);

        // Progression Rama : M01 + M02 complets ; M03 leçon 1 complète, leçon 2 à 47 %
        foreach ($lessonsByModule[0] as $lesson) {
            $p = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
            $p->recordWatch($lesson->getDurationSeconds(), 100);
            $p->markCompleted();
            $manager->persist($p);
        }
        foreach ($lessonsByModule[1] as $lesson) {
            $p = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
            $p->recordWatch($lesson->getDurationSeconds(), 100);
            $p->markCompleted();
            $manager->persist($p);
        }
        // M03 leçon 1 complète
        $m3l1 = $lessonsByModule[2][0];
        $p = (new LessonProgress())->setEnrollment($enrollment)->setLesson($m3l1);
        $p->recordWatch($m3l1->getDurationSeconds(), 100);
        $p->markCompleted();
        $manager->persist($p);

        // M03 leçon 2 — en cours à 47 %
        $m3l2 = $lessonsByModule[2][1];
        $p = (new LessonProgress())->setEnrollment($enrollment)->setLesson($m3l2);
        $p->recordWatch((int) round($m3l2->getDurationSeconds() * 0.47), 47);
        $manager->persist($p);

        $manager->flush();
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
