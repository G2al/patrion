<?php

declare(strict_types=1);

namespace App\Filament\Resources\Practices\Pages;

use App\Filament\Resources\Practices\PracticeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPractices extends ListRecords
{
    protected static string $resource = PracticeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuova pratica')];
    }
}
