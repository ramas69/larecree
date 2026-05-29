<?php

declare(strict_types=1);

namespace App\Entity;

enum ResourceType: string
{
    case Link = 'link';
    case File = 'file';
}
