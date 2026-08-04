<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Prospects\ProspectResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProspectResource::convertAction(),
            Action::make('new_appointment')->label('Nuovo appuntamento')->url(fn (): string => AppointmentResource::getUrl('create', ['contact_id' => $this->getRecord()->getKey()])),
            Action::make('new_activity')->label('Nuova attività')->url(fn (): string => ActivityResource::getUrl('create', ['contact_id' => $this->getRecord()->getKey()])),
            Action::make('new_practice')->label('Nuova pratica')->url(fn (): string => PracticeResource::getUrl('create', ['contact_id' => $this->getRecord()->getKey()])),
            Action::make('new_document')->label('Nuovo documento')->url(fn (): string => DocumentResource::getUrl('create', ['contact_id' => $this->getRecord()->getKey()])),
            Action::make('new_note')->label('Nuova nota')->schema([TextInput::make('title')->label('Titolo'), Textarea::make('content')->label('Contenuto')->required(), Toggle::make('is_important')->label('Importante')])->action(fn (array $data) => $this->getRecord()->notes()->create([...$data, 'author_id' => auth()->id()])),
            EditAction::make(),
        ];
    }
}
