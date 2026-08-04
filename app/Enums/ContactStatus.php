<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactStatus: string
{
    case Prospect = 'prospect';
    case Client = 'client';
}
