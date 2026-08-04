<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $relatedResource = ActivityResource::class;

    protected static ?string $title = 'Attività';

    public function table(Table $table): Table
    {
        return $table->headerActions([
            CreateAction::make()->label('Nuova attività')->mutateDataUsing(fn (array $data): array => [...$data, 'owner_id' => auth()->id()]),
        ]);
    }
}
