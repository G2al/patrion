<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';
}
