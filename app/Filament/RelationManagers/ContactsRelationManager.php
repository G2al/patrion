<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Support\ItalianOptions;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Referenti';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('role')->label('Ruolo aziendale')->options(ItalianOptions::COMPANY_ROLES)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last_name')
            ->columns([
                TextColumn::make('first_name')->label('Nome')->searchable(),
                TextColumn::make('last_name')->label('Cognome')->searchable(),
                TextColumn::make('email')->label('Email'),
                TextColumn::make('phone')->label('Telefono'),
                TextColumn::make('role')->label('Ruolo')->badge()->formatStateUsing(fn (?string $state): string => ItalianOptions::COMPANY_ROLES[$state] ?? $state ?? '-'),
            ])
            ->headerActions([
                AttachAction::make()->label('Collega contatto')->preloadRecordSelect()->schema(fn (AttachAction $action): array => [
                    $action->getRecordSelect()->label('Contatto'),
                    Select::make('role')->label('Ruolo aziendale')->options(ItalianOptions::COMPANY_ROLES)->required(),
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('Modifica ruolo'),
                DetachAction::make()->label('Scollega'),
            ]);
    }
}
