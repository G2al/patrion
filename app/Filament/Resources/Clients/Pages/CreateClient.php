<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Pages;

use App\Enums\ContactStatus;
use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ContactStatus::Client;

        return $data;
    }
}
