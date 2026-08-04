<?php

declare(strict_types=1);

namespace App\Enums;

enum ProspectSource: string
{
    case Event = 'event';
    case Referral = 'referral';
    case LinkedIn = 'linkedin';
    case Instagram = 'instagram';
    case Client = 'client';
    case ColdCall = 'cold_call';
    case Other = 'other';
}
