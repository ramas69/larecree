<?php

declare(strict_types=1);

namespace App\Catalogue;

use App\Entity\Formation;

final class FormationCard
{
    public function __construct(
        public readonly Formation $formation,
        public readonly bool $enrolled,
        public readonly bool $vipGranted,
        public readonly int $modulesCount,
        public readonly int $lessonsCount,
        public readonly int $totalSeconds,
        public readonly int $progressPercent,
        public readonly int $completedLessonsCount,
    ) {
    }

    public function totalDurationFormatted(): string
    {
        if ($this->totalSeconds < 60) {
            return '0 min';
        }
        $hours = intdiv($this->totalSeconds, 3600);
        $minutes = intdiv($this->totalSeconds % 3600, 60);
        if ($hours === 0) {
            return $minutes.' min';
        }

        return $minutes === 0 ? $hours.' h' : sprintf('%d h %02d', $hours, $minutes);
    }
}
