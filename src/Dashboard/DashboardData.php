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
        $primary = $userEnrollments[0] ?? null;

        $totalSeconds = 0;
        $completedCount = 0;
        $progressSum = 0;
        $progressDen = 0;

        foreach ($userEnrollments as $enrollment) {
            foreach ($enrollment->getProgresses() as $progress) {
                $totalSeconds += $progress->getWatchedSeconds();
                if ($progress->isCompleted()) {
                    $completedCount++;
                }
                $progressSum += $progress->getPercentWatched();
                $progressDen++;
            }
        }

        $avgProgress = $progressDen > 0 ? (int) round($progressSum / $progressDen) : 0;

        $current = $primary !== null ? $this->pickCurrentProgress($primary) : null;
        $progressMap = $primary !== null ? $this->buildProgressMap($primary) : null;

        return new DashboardSnapshot(
            enrollmentsInProgressCount: count($userEnrollments),
            totalWatchedHours: $this->formatHours($totalSeconds),
            averageProgressPercent: $avgProgress,
            certificatesCount: 0,
            primaryEnrollment: $primary,
            currentProgress: $current,
            progressMap: $progressMap,
        );
    }

    private function pickCurrentProgress(Enrollment $enrollment): ?LessonProgress
    {
        $rows = $this->progresses->findByEnrollment($enrollment);
        foreach ($rows as $row) {
            if (!$row->isCompleted()) {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }

    /**
     * @return ProgressMap
     */
    private function buildProgressMap(Enrollment $enrollment): ProgressMap
    {
        $formation = $enrollment->getFormation();
        $modules = $formation !== null ? $formation->getModules()->toArray() : [];

        $completedLessonIds = [];
        $watchedLessonIds = [];
        foreach ($enrollment->getProgresses() as $progress) {
            $lesson = $progress->getLesson();
            if ($lesson === null) {
                continue;
            }
            if ($progress->isCompleted()) {
                $completedLessonIds[$lesson->getId()] = true;
            }
            $watchedLessonIds[$lesson->getId()] = $progress->getPercentWatched();
        }

        $items = [];
        $doneModules = 0;
        $foundCurrent = false;
        $totalModules = count($modules);
        $lastDoneTitle = null;

        foreach ($modules as $i => $module) {
            /** @var Module $module */
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
            $isLast = $i === $totalModules - 1;
            $state = 'upcoming';

            if ($totalLessons > 0 && $doneLessons === $totalLessons) {
                $state = $isLast ? 'finale-done' : 'done';
                $doneModules++;
                $lastDoneTitle = $module->getTitle();
            } elseif (!$foundCurrent && $modulePercent > 0) {
                $state = 'current';
                $foundCurrent = true;
            } elseif ($isLast) {
                $state = 'finale';
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
