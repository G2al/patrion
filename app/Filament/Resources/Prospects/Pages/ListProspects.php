<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuovo prospect')];
    }
}
