<?php

declare(strict_types=1);

namespace App\Filament\Resources\Practices;

use App\Enums\PracticeStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\RelationManagers\AppointmentsRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Practices\Pages\CreatePractice;
use App\Filament\Resources\Practices\Pages\EditPractice;
use App\Filament\Resources\Practices\Pages\ListPractices;
use App\Filament\Resources\Practices\Pages\ViewPractice;
use App\Filament\Support\ItalianOptions;
use App\Models\Practice;
use App\Models\PracticeType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PracticeResource extends Resource
{
    protected static ?string $model = Practice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Pratiche';

    protected static ?string $modelLabel = 'pratica';

    protected static ?string $pluralModelLabel = 'pratiche';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pratica')->schema([
                TextInput::make('internal_number')->label('Codice interno')->required()->unique(ignoreRecord: true),
                TextInput::make('title')->label('Titolo')->required(),
                Textarea::make('description')->label('Descrizione')->columnSpanFull(),
                Select::make('practice_type_id')->label('Tipologia')->options(fn (?Practice $record): array => PracticeType::query()->where('is_active', true)->when($record, fn (Builder $query): Builder => $query->orWhereKey($record->practice_type_id))->ordered()->pluck('name', 'id')->all())->searchable()->required(),
                Select::make('status')->label('Stato')->options(ItalianOptions::PRACTICE_STATUSES)->required()->default('draft'),
                Select::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES)->required()->default('medium'),
            ])->columns(2),
            Section::make('Soggetto principale')->description('Seleziona un contatto oppure un’azienda, mai entrambi.')->schema([
                Select::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable()->preload()->live()->default(request()->integer('contact_id') ?: null)->required(fn (Get $get): bool => blank($get('company_id')))->disabled(fn (Get $get): bool => filled($get('company_id'))),
                Select::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable()->preload()->live()->default(request()->integer('company_id') ?: null)->required(fn (Get $get): bool => blank($get('contact_id')))->disabled(fn (Get $get): bool => filled($get('contact_id'))),
            ])->columns(2),
            Section::make('Date e valori')->schema([
                DatePicker::make('opened_at')->label('Data apertura')->required()->default(today()),
                DatePicker::make('expected_at')->label('Data prevista'),
                TextInput::make('expected_value')->label('Valore previsto')->numeric()->prefix('€'),
                TextInput::make('actual_value')->label('Valore effettivo')->numeric()->prefix('€'),
                TextInput::make('outcome')->label('Esito'),
                Textarea::make('notes')->label('Note')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Pratica')->schema([
            TextEntry::make('internal_number')->label('Codice'), TextEntry::make('title')->label('Titolo'),
            TextEntry::make('practiceType.name')->label('Tipologia'), TextEntry::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRACTICE_STATUSES[$state?->value] ?? '-'),
            TextEntry::make('contact.last_name')->label('Contatto'), TextEntry::make('company.name')->label('Azienda'),
            TextEntry::make('opened_at')->label('Aperta il')->date('d/m/Y'), TextEntry::make('completed_at')->label('Completata il')->date('d/m/Y'),
            TextEntry::make('description')->label('Descrizione')->columnSpanFull(),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contact', 'company', 'practiceType']))->columns([
            TextColumn::make('internal_number')->label('Codice')->searchable()->sortable(),
            TextColumn::make('title')->label('Titolo')->searchable()->sortable(),
            TextColumn::make('subject')->label('Soggetto')->state(fn (Practice $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name),
            TextColumn::make('practiceType.name')->label('Tipologia')->sortable(),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRACTICE_STATUSES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
            TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-'),
            TextColumn::make('opened_at')->label('Apertura')->date('d/m/Y')->sortable(),
            TextColumn::make('completed_at')->label('Completamento')->date('d/m/Y')->sortable(),
        ])->filters([
            SelectFilter::make('practice_type_id')->label('Tipologia')->relationship('practiceType', 'name'),
            SelectFilter::make('status')->label('Stato')->options(ItalianOptions::PRACTICE_STATUSES),
            SelectFilter::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES),
            SelectFilter::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable(),
            SelectFilter::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable(),
            Filter::make('completed_period')->label('Periodo completamento')->schema([DatePicker::make('from')->label('Dal'), DatePicker::make('until')->label('Al')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('completed_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('completed_at', '<=', $date))),
            Filter::make('opened_period')->label('Periodo apertura')->schema([DatePicker::make('from')->label('Dal'), DatePicker::make('until')->label('Al')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('opened_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('opened_at', '<=', $date))),
            Filter::make('stationary')->label('Pratiche ferme')->query(fn (Builder $query): Builder => $query->whereIn('status', [PracticeStatus::InProgress, PracticeStatus::Waiting])->where('updated_at', '<=', now()->subDays(7))),
        ])->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [AppointmentsRelationManager::class, ActivitiesRelationManager::class, DocumentsRelationManager::class, NotesRelationManager::class];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['internal_number', 'title', 'contact.first_name', 'contact.last_name', 'company.name', 'practiceType.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Practice::query()->where('status', PracticeStatus::Waiting)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->getAttribute('internal_number')} · {$record->getAttribute('title')}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Practice $record */
        return ['Soggetto' => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name, 'Tipologia' => (string) $record->practiceType?->name];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $query->with(['contact', 'company', 'practiceType']);
    }

    public static function getPages(): array
    {
        return ['index' => ListPractices::route('/'), 'create' => CreatePractice::route('/create'), 'view' => ViewPractice::route('/{record}'), 'edit' => EditPractice::route('/{record}/edit')];
    }
}
