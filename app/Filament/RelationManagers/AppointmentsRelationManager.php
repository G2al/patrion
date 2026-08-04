<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $relatedResource = AppointmentResource::class;

    protected static ?string $title = 'Appuntamenti';

    public function table(Table $table): Table
    {
        return $table->headerActions([
            CreateAction::make()->label('Nuovo appuntamento')->mutateDataUsing(fn (array $data): array => [...$data, 'owner_id' => auth()->id()]),
        ]);
    }
}
