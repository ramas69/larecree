<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\LessonProgress;

final class DashboardSnapshot
{
    /**
     * @param EnrollmentSnapshot[] $enrollmentSnapshots
     */
    public function __construct(
        public readonly int $enrollmentsInProgressCount,
        public readonly string $totalWatchedHours,
        public readonly int $averageProgressPercent,
        public readonly int $certificatesCount,
        public readonly ?LessonProgress $currentProgress,
        public readonly array $enrollmentSnapshots,
    ) {
    }
}
