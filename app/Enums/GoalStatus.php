<?php

declare(strict_types=1);

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Achieved = 'achieved';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
