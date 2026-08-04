<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Note';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Titolo')->maxLength(255),
            Textarea::make('content')->label('Contenuto')->required()->rows(5)->columnSpanFull(),
            Toggle::make('is_important')->label('Importante'),
            Hidden::make('author_id')->default(fn (): ?int => auth()->id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_important')->label('Importante')->boolean()->trueColor('danger'),
                TextColumn::make('title')->label('Titolo')->placeholder('Senza titolo')->searchable(),
                TextColumn::make('content')->label('Contenuto')->limit(100)->wrap(),
                TextColumn::make('author.name')->label('Autore'),
                TextColumn::make('created_at')->label('Creata il')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([CreateAction::make()->label('Nuova nota')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
