<?php

declare(strict_types=1);

namespace App\Entity;

enum EnrollmentSource: string
{
    case Stripe = 'stripe';
    case Vip    = 'vip';
    case Admin  = 'admin';
}
