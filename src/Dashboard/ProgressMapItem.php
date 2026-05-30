<?php

declare(strict_types=1);

namespace App\Dashboard;

final class ProgressMapItem
{
    public function __construct(
        public readonly int $index,
        public readonly string $label,
        public readonly string $title,
        public readonly string $state,
        public readonly int $percent,
    ) {
    }
}
