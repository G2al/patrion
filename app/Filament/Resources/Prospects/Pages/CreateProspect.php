<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Pages;

use App\Enums\ContactStatus;
use App\Filament\Resources\Prospects\ProspectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProspect extends CreateRecord
{
    protected static string $resource = ProspectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ContactStatus::Prospect;

        return $data;
    }
}
