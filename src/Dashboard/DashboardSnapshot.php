<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\Lesson;
use App\Entity\LessonProgress;

final class DashboardSnapshot
{
    /**
     * @param EnrollmentSnapshot[] $enrollmentSnapshots
     */
    public function __construct(
        public readonly string $totalWatchedHours,
        public readonly ?LessonProgress $currentProgress,
        public readonly array $enrollmentSnapshots,
        // Quand rien n'est en cours : la première leçon pas encore terminée, dans l'ordre du programme.
        public readonly ?Lesson $nextLesson = null,
    ) {
    }
}
