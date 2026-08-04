<?php

declare(strict_types=1);

namespace App\Filament\Resources\PracticeTypes\Pages;

use App\Filament\Resources\PracticeTypes\PracticeTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePracticeType extends CreateRecord
{
    protected static string $resource = PracticeTypeResource::class;
}
