<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentOutcome: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Postponed = 'postponed';
    case ToFollowUp = 'to_follow_up';
}
