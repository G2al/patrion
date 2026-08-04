<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\RelationManagers\AppointmentsRelationManager;
use App\Filament\RelationManagers\ContactsRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\RelationManagers\PracticesRelationManager;
use App\Filament\RelationManagers\TimelineEventsRelationManager;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Relazioni';

    protected static ?string $navigationLabel = 'Aziende';

    protected static ?string $modelLabel = 'azienda';

    protected static ?string $pluralModelLabel = 'aziende';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Scheda azienda')->tabs([
                Tab::make('Informazioni generali')->schema([
                    TextInput::make('name')->label('Ragione sociale')->required()->maxLength(255),
                    TextInput::make('vat_number')->label('Partita IVA')->unique(ignoreRecord: true),
                    TextInput::make('tax_code')->label('Codice fiscale')->unique(ignoreRecord: true),
                    TextInput::make('rea_number')->label('Numero REA'),
                ])->columns(2),
                Tab::make('Contatti e fatturazione')->schema([
                    Textarea::make('address')->label('Indirizzo')->rows(3)->columnSpanFull(),
                    TextInput::make('phone')->label('Telefono')->tel(),
                    TextInput::make('email')->label('Email')->email(),
                    TextInput::make('website')->label('Sito web')->url(),
                    TextInput::make('pec')->label('PEC')->email(),
                    TextInput::make('sdi_code')->label('Codice SDI'),
                ])->columns(2),
                Tab::make('Attività aziendale')->schema([
                    TextInput::make('industry')->label('Settore'),
                    TextInput::make('ateco_code')->label('Codice ATECO'),
                    TextInput::make('revenue')->label('Fatturato')->numeric()->prefix('€'),
                    TextInput::make('employees_count')->label('Numero di dipendenti')->numeric()->minValue(0),
                    TextInput::make('shareholders_count')->label('Numero di soci')->numeric()->minValue(0),
                ])->columns(2),
                Tab::make('Informazioni patrimoniali')->schema([
                    TextInput::make('liquidity')->label('Liquidità')->numeric()->prefix('€'),
                    TextInput::make('investments')->label('Investimenti')->numeric()->prefix('€'),
                    TextInput::make('financing')->label('Finanziamenti')->numeric()->prefix('€'),
                    TextInput::make('insurance')->label('Assicurazioni')->numeric()->prefix('€'),
                    TextInput::make('pension')->label('Previdenza')->numeric()->prefix('€'),
                ])->columns(2),
                Tab::make('Opportunità')->schema([
                    Select::make('opportunities')
                        ->label('Aree di interesse')
                        ->multiple()
                        ->searchable()
                        ->options([
                            'investments' => 'Investimenti',
                            'liquidity' => 'Liquidità',
                            'financing' => 'Finanziamenti',
                            'welfare' => 'Welfare',
                            'pension' => 'Previdenza',
                            'protection' => 'Protezione',
                        ]),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informazioni principali')->schema([
                TextEntry::make('name')->label('Ragione sociale'),
                TextEntry::make('vat_number')->label('Partita IVA'),
                TextEntry::make('tax_code')->label('Codice fiscale'),
                TextEntry::make('phone')->label('Telefono'),
                TextEntry::make('email')->label('Email'),
                TextEntry::make('pec')->label('PEC'),
                TextEntry::make('address')->label('Indirizzo')->columnSpanFull(),
            ])->columns(3),
            Section::make('Attività aziendale')->schema([
                TextEntry::make('industry')->label('Settore'),
                TextEntry::make('ateco_code')->label('Codice ATECO'),
                TextEntry::make('revenue')->label('Fatturato')->money('EUR'),
                TextEntry::make('employees_count')->label('Dipendenti'),
                TextEntry::make('shareholders_count')->label('Soci'),
            ])->columns(3)->collapsible(),
            Section::make('Informazioni patrimoniali e opportunità')->schema([
                TextEntry::make('liquidity')->label('Liquidità')->money('EUR'),
                TextEntry::make('investments')->label('Investimenti')->money('EUR'),
                TextEntry::make('financing')->label('Finanziamenti')->money('EUR'),
                TextEntry::make('opportunities')->label('Aree di interesse')->badge()->columnSpanFull(),
            ])->columns(3)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Ragione sociale')->searchable()->sortable(),
            TextColumn::make('vat_number')->label('Partita IVA')->searchable(),
            TextColumn::make('industry')->label('Settore')->searchable()->sortable(),
            TextColumn::make('phone')->label('Telefono'),
            TextColumn::make('email')->label('Email'),
            TextColumn::make('contacts_count')->counts('contacts')->label('Referenti')->badge(),
        ])->filters([
            SelectFilter::make('industry')->label('Settore')->options(fn (): array => Company::query()->whereNotNull('industry')->distinct()->pluck('industry', 'industry')->all()),
            SelectFilter::make('ateco_code')->label('Codice ATECO')->options(fn (): array => Company::query()->whereNotNull('ateco_code')->distinct()->pluck('ateco_code', 'ateco_code')->all())->searchable(),
            Filter::make('revenue_range')->label('Fascia di fatturato')->schema([TextInput::make('min')->label('Da')->numeric()->prefix('€'), TextInput::make('max')->label('A')->numeric()->prefix('€')])->query(fn (Builder $query, array $data): Builder => $query->when($data['min'] ?? null, fn (Builder $query, $value): Builder => $query->where('revenue', '>=', $value))->when($data['max'] ?? null, fn (Builder $query, $value): Builder => $query->where('revenue', '<=', $value))),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ContactsRelationManager::class, AppointmentsRelationManager::class, ActivitiesRelationManager::class, PracticesRelationManager::class, DocumentsRelationManager::class, NotesRelationManager::class, TimelineEventsRelationManager::class];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'vat_number', 'tax_code', 'pec', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->getAttribute('name');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Partita IVA' => $record->getAttribute('vat_number') ?: '-', 'Email' => $record->getAttribute('email') ?: '-'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
