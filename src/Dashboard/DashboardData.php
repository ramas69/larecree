<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\LessonProgressRepository;

final class DashboardData
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly LessonProgressRepository $progresses,
    ) {
    }

    public function forUser(User $user): DashboardSnapshot
    {
        $userEnrollments = $this->enrollments->findByUser($user);

        $totalSeconds = 0;
        $progressSum = 0;
        $progressDen = 0;
        $currentCandidate = null;

        $enrollmentSnapshots = [];
        foreach ($userEnrollments as $enrollment) {
            foreach ($enrollment->getProgresses() as $progress) {
                $totalSeconds += $progress->getWatchedSeconds();
                $progressSum += $progress->getPercentWatched();
                $progressDen++;
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

        $avgProgress = $progressDen > 0 ? (int) round($progressSum / $progressDen) : 0;

        return new DashboardSnapshot(
            enrollmentsInProgressCount: count($userEnrollments),
            totalWatchedHours: $this->formatHours($totalSeconds),
            averageProgressPercent: $avgProgress,
            certificatesCount: 0,
            currentProgress: $currentCandidate ?? $this->fallbackCurrent($userEnrollments),
            enrollmentSnapshots: $enrollmentSnapshots,
        );
    }

    /**
     * @param Enrollment[] $userEnrollments
     */
    private function fallbackCurrent(array $userEnrollments): ?LessonProgress
    {
        foreach ($userEnrollments as $enrollment) {
            $rows = $this->progresses->findByEnrollment($enrollment);
            if ($rows !== []) {
                return $rows[0];
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

        foreach ($modules as $i => $module) {
            /** @var Module $module */
            [$doneLessons, $totalLessons, $modulePercent] = $this->summarizeModule($module, $completedLessonIds, $watchedLessonIds);
            $isLast = $i === $totalModules - 1;
            $state = $this->resolveState($doneLessons, $totalLessons, $modulePercent, $isLast, $foundCurrent);

            if ($state === 'done' || $state === 'finale-done') {
                $doneModules++;
                $lastDoneTitle = $module->getTitle();
            } elseif ($state === 'current') {
                $foundCurrent = true;
            }

            $items[] = new ProgressMapItem(
                index: $i + 1,
                label: 'M'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                title: $module->getTitle(),
                state: $state,
                percent: $modulePercent,
            );
        }

        $overall = $totalModules > 0
            ? (int) round(array_sum(array_map(fn (ProgressMapItem $p) => $p->percent, $items)) / $totalModules)
            : 0;

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
