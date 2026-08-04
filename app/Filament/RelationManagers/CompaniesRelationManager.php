<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Support\ItalianOptions;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'Aziende collegate';

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('name')->columns([
            TextColumn::make('name')->label('Ragione sociale')->searchable(),
            TextColumn::make('industry')->label('Settore'),
            TextColumn::make('role')->label('Ruolo')->badge()->formatStateUsing(fn (?string $state): string => ItalianOptions::COMPANY_ROLES[$state] ?? $state ?? '-'),
        ])->headerActions([
            AttachAction::make()->label('Collega azienda')->preloadRecordSelect()->schema(fn (AttachAction $action): array => [
                $action->getRecordSelect()->label('Azienda'),
                Select::make('role')->label('Ruolo aziendale')->options(ItalianOptions::COMPANY_ROLES)->required(),
            ]),
        ])->recordActions([DetachAction::make()->label('Scollega')]);
    }
}
