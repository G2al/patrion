<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Valid = 'valid';
    case Expired = 'expired';
    case Archived = 'archived';
}
