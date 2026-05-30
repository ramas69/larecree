<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\Enrollment;

final class EnrollmentSnapshot
{
    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly ProgressMap $progressMap,
    ) {
    }
}
