<?php

declare(strict_types=1);

namespace App\Filament\Resources\PracticeTypes\Pages;

use App\Filament\Resources\PracticeTypes\PracticeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPracticeType extends EditRecord
{
    protected static string $resource = PracticeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->disabled(fn ($record): bool => $record->practices()->exists())];
    }
}
