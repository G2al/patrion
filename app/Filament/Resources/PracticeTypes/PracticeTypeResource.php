<?php

declare(strict_types=1);

namespace App\Filament\Resources\PracticeTypes;

use App\Filament\Resources\PracticeTypes\Pages\CreatePracticeType;
use App\Filament\Resources\PracticeTypes\Pages\EditPracticeType;
use App\Filament\Resources\PracticeTypes\Pages\ListPracticeTypes;
use App\Models\PracticeType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PracticeTypeResource extends Resource
{
    protected static ?string $model = PracticeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni';

    protected static ?string $navigationLabel = 'Tipologie di pratica';

    protected static ?string $modelLabel = 'tipologia di pratica';

    protected static ?string $pluralModelLabel = 'tipologie di pratica';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tipologia')->schema([
                TextInput::make('name')->label('Nome')->required()->unique(ignoreRecord: true)->live(onBlur: true)->afterStateUpdated(fn (?string $state, callable $set): mixed => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description')->label('Descrizione')->columnSpanFull(),
                ColorPicker::make('color')->label('Colore'),
                Toggle::make('is_active')->label('Attiva')->default(true),
                TextInput::make('sort_order')->label('Ordine')->numeric()->default(0)->minValue(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make('sort_order')->label('Ordine')->sortable(),
            TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            TextColumn::make('slug')->label('Slug'),
            ColorColumn::make('color')->label('Colore'),
            IconColumn::make('is_active')->label('Attiva')->boolean(),
            TextColumn::make('practices_count')->counts('practices')->label('Pratiche')->badge(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPracticeTypes::route('/'), 'create' => CreatePracticeType::route('/create'), 'edit' => EditPracticeType::route('/{record}/edit')];
    }
}
