<?php

declare(strict_types=1);

namespace App\Dashboard;

final class ProgressMap
{
    /**
     * @param ProgressMapItem[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $doneModulesCount,
        public readonly int $totalModulesCount,
        public readonly int $overallPercent,
        public readonly ?string $lastCompletedTitle,
    ) {
    }
}
