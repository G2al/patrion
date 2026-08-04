<?php

declare(strict_types=1);

namespace App\Filament\Resources\Goals\Pages;

use App\Filament\Resources\Goals\GoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGoal extends CreateRecord
{
    protected static string $resource = GoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();

        return $data;
    }
}
