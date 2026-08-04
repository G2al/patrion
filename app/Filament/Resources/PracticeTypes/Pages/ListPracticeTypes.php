<?php

declare(strict_types=1);

namespace App\Filament\Resources\PracticeTypes\Pages;

use App\Filament\Resources\PracticeTypes\PracticeTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPracticeTypes extends ListRecords
{
    protected static string $resource = PracticeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuova tipologia')];
    }
}
