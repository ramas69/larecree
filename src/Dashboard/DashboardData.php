<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\EnrollmentRepository;

final class DashboardData
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
    ) {
    }

    public function forUser(User $user): DashboardSnapshot
    {
        $userEnrollments = $this->enrollments->findByUser($user);

        $totalSeconds = 0;
        $currentCandidate = null;

        $enrollmentSnapshots = [];
        foreach ($userEnrollments as $enrollment) {
            foreach ($enrollment->getProgresses() as $progress) {
                $totalSeconds += $progress->getWatchedSeconds();
                if (!$progress->isCompleted()
                    && ($currentCandidate === null || $progress->getLastWatchedAt() > $currentCandidate->getLastWatchedAt())) {
                    $currentCandidate = $progress;
                }
            }

            $enrollmentSnapshots[] = new EnrollmentSnapshot(
                enrollment: $enrollment,
                progressMap: $this->buildProgressMap($enrollment),
            );
        }

        return new DashboardSnapshot(
            totalWatchedHours: $this->formatHours($totalSeconds),
            currentProgress: $currentCandidate,
            enrollmentSnapshots: $enrollmentSnapshots,
            nextLesson: $currentCandidate === null ? $this->nextLesson($userEnrollments) : null,
        );
    }

    /**
     * Avant : la dernière leçon vue, même terminée (« Reprendre » une leçon vue à 100 %).
     * Maintenant : la première leçon pas encore terminée, dans l'ordre du programme.
     *
     * @param Enrollment[] $userEnrollments
     */
    private function nextLesson(array $userEnrollments): ?Lesson
    {
        foreach ($userEnrollments as $enrollment) {
            [$completed] = $this->indexLessonProgress($enrollment);
            foreach ($enrollment->getFormation()?->getModules() ?? [] as $module) {
                foreach ($module->getLessons() as $lesson) {
                    if (!isset($completed[$lesson->getId()])) {
                        return $lesson;
                    }
                }
            }
        }

        return null;
    }

    private function buildProgressMap(Enrollment $enrollment): ProgressMap
    {
        $formation = $enrollment->getFormation();
        $modules = $formation !== null ? $formation->getModules()->toArray() : [];
        [$completedLessonIds, $watchedLessonIds] = $this->indexLessonProgress($enrollment);

        $items = [];
        $doneModules = 0;
        $foundCurrent = false;
        $totalModules = count($modules);
        $lastDoneTitle = null;
        $doneLessonsAll = 0;
        $totalLessonsAll = 0;

        foreach ($modules as $i => $module) {
            /** @var Module $module */
            [$doneLessons, $totalLessons, $modulePercent] = $this->summarizeModule($module, $completedLessonIds, $watchedLessonIds);
            $isLast = $i === $totalModules - 1;
            $doneLessonsAll += $doneLessons;
            $totalLessonsAll += $totalLessons;
            $state = $this->resolveState($doneLessons, $totalLessons, $modulePercent, $isLast, $foundCurrent);

            if ($state === 'done' || $state === 'finale-done') {
                $doneModules++;
                $lastDoneTitle = $module->getNom();
            } elseif ($state === 'current') {
                $foundCurrent = true;
            }

            $items[] = new ProgressMapItem(
                index: $i + 1,
                label: 'M'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                title: $module->getNom(),
                state: $state,
                percent: $modulePercent,
            );
        }

        // Même calcul que le catalogue et la page formation : leçons terminées / leçons de la formation.
        $overall = $totalLessonsAll > 0 ? (int) round($doneLessonsAll * 100 / $totalLessonsAll) : 0;

        return new ProgressMap(
            items: $items,
            doneModulesCount: $doneModules,
            totalModulesCount: $totalModules,
            overallPercent: $overall,
            lastCompletedTitle: $lastDoneTitle,
        );
    }

    /**
     * @return array{0: array<int, true>, 1: array<int, int>}
     */
    private function indexLessonProgress(Enrollment $enrollment): array
    {
        $completed = [];
        $watched = [];
        foreach ($enrollment->getProgresses() as $progress) {
            $lesson = $progress->getLesson();
            if ($lesson === null) {
                continue;
            }
            if ($progress->isCompleted()) {
                $completed[$lesson->getId()] = true;
            }
            $watched[$lesson->getId()] = $progress->getPercentWatched();
        }

        return [$completed, $watched];
    }

    /**
     * @param array<int, true> $completedLessonIds
     * @param array<int, int>  $watchedLessonIds
     * @return array{0: int, 1: int, 2: int} [doneLessons, totalLessons, modulePercent]
     */
    private function summarizeModule(Module $module, array $completedLessonIds, array $watchedLessonIds): array
    {
        $lessons = $module->getLessons()->toArray();
        $totalLessons = count($lessons);
        $doneLessons = 0;
        $sumPercent = 0;
        foreach ($lessons as $l) {
            /** @var Lesson $l */
            $lid = $l->getId();
            if (isset($completedLessonIds[$lid])) {
                $doneLessons++;
            }
            $sumPercent += $watchedLessonIds[$lid] ?? 0;
        }
        $modulePercent = $totalLessons > 0 ? (int) round($sumPercent / $totalLessons) : 0;

        return [$doneLessons, $totalLessons, $modulePercent];
    }

    private function resolveState(int $doneLessons, int $totalLessons, int $modulePercent, bool $isLast, bool $currentAlreadyFound): string
    {
        if ($totalLessons > 0 && $doneLessons === $totalLessons) {
            return $isLast ? 'finale-done' : 'done';
        }
        if (!$currentAlreadyFound && $modulePercent > 0) {
            return 'current';
        }

        return $isLast ? 'finale' : 'upcoming';
    }

    private function formatHours(int $seconds): string
    {
        if ($seconds < 60) {
            return '0 min';
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        if ($hours === 0) {
            return $minutes.' min';
        }

        return $minutes === 0 ? $hours.' h' : sprintf('%d h %02d', $hours, $minutes);
    }
}
