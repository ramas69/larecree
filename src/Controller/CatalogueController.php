<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalogue\FormationCard;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CatalogueController extends AbstractController
{
    #[Route('/formations', name: 'app_catalogue')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(FormationRepository $formations, EnrollmentRepository $enrollments): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userEnrollments = $enrollments->findByUser($user);
        $byFormationId = [];
        foreach ($userEnrollments as $e) {
            $f = $e->getFormation();
            if ($f !== null) {
                $byFormationId[$f->getId()] = $e;
            }
        }

        $cards = [];
        $totalModules = 0;
        $totalLessons = 0;
        foreach ($formations->findPublished() as $formation) {
            $card = $this->buildCard($formation, $byFormationId[$formation->getId()] ?? null);
            $cards[] = $card;
            $totalModules += $card->modulesCount;
            $totalLessons += $card->lessonsCount;
        }

        return $this->render('catalogue/index.html.twig', [
            'cards'         => $cards,
            'enrolledCount' => count($userEnrollments),
            'totalFormations' => count($cards),
            'totalModules'  => $totalModules,
            'totalLessons'  => $totalLessons,
        ]);
    }

    private function buildCard(Formation $formation, ?Enrollment $enrollment): FormationCard
    {
        $modulesCount = $formation->getModules()->count();
        $lessonsCount = 0;
        $totalSeconds = 0;
        $lessonIds = [];
        foreach ($formation->getModules() as $module) {
            /** @var Module $module */
            foreach ($module->getLessons() as $lesson) {
                $lessonsCount++;
                $totalSeconds += $lesson->getDurationSeconds();
                $lessonIds[$lesson->getId()] = true;
            }
        }

        [$progressPercent, $completedLessons] = $this->resolveProgress($enrollment, $lessonIds, $lessonsCount);

        return new FormationCard(
            formation: $formation,
            enrolled: $enrollment !== null,
            vipGranted: $enrollment !== null && $enrollment->isVipGranted(),
            modulesCount: $modulesCount,
            lessonsCount: $lessonsCount,
            totalSeconds: $totalSeconds,
            progressPercent: $progressPercent,
            completedLessonsCount: $completedLessons,
        );
    }

    /**
     * @param array<int, true> $lessonIds
     * @return array{0: int, 1: int}
     */
    private function resolveProgress(?Enrollment $enrollment, array $lessonIds, int $lessonsCount): array
    {
        if ($enrollment === null || $lessonsCount === 0) {
            return [0, 0];
        }
        $sum = 0;
        $completed = 0;
        foreach ($enrollment->getProgresses() as $progress) {
            $lesson = $progress->getLesson();
            if ($lesson === null || !isset($lessonIds[$lesson->getId()])) {
                continue;
            }
            $sum += $progress->getPercentWatched();
            if ($progress->isCompleted()) {
                $completed++;
            }
        }

        return [(int) round($sum / $lessonsCount), $completed];
    }
}
