<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimelineEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'timelineEvents';

    protected static ?string $title = 'Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')->label('Data e ora')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('title')->label('Evento')->weight('bold')->searchable(),
                TextColumn::make('description')->label('Descrizione')->wrap(),
                TextColumn::make('author.name')->label('Autore')->placeholder('Sistema'),
            ]);
    }
}
