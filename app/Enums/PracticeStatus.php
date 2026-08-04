<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticeStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Unsuccessful = 'unsuccessful';
    case Cancelled = 'cancelled';
}
