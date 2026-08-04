<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Support\ItalianOptions;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Documenti';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documenti';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Documento')->schema([
            TextInput::make('name')->label('Nome')->required(),
            Select::make('category')->label('Categoria')->options(ItalianOptions::DOCUMENT_CATEGORIES)->searchable(),
            FileUpload::make('file_path')->label('File')->disk('local')->visibility('private')->directory('documents')->required(),
            Hidden::make('disk')->default('local'),
            Textarea::make('description')->label('Descrizione')->columnSpanFull(),
            Select::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable()->preload()->default(request()->integer('contact_id') ?: null),
            Select::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable()->preload()->default(request()->integer('company_id') ?: null),
            Select::make('practice_id')->label('Pratica')->relationship('practice', 'title')->searchable()->preload(),
            DatePicker::make('document_date')->label('Data documento'), DatePicker::make('expires_at')->label('Scadenza'),
            Select::make('status')->label('Stato')->options(ItalianOptions::DOCUMENT_STATUSES)->default('valid')->required(),
            Textarea::make('notes')->label('Note')->columnSpanFull(),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contact', 'company', 'practice']))->columns([
            TextColumn::make('name')->label('Nome')->searchable(), TextColumn::make('category')->label('Categoria')->badge(),
            TextColumn::make('contact.last_name')->label('Contatto'), TextColumn::make('company.name')->label('Azienda'), TextColumn::make('practice.title')->label('Pratica'),
            TextColumn::make('expires_at')->label('Scadenza')->date('d/m/Y')->sortable(),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::DOCUMENT_STATUSES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
        ])->filters([
            SelectFilter::make('category')->label('Categoria')->options(ItalianOptions::DOCUMENT_CATEGORIES),
            SelectFilter::make('status')->label('Stato')->options(ItalianOptions::DOCUMENT_STATUSES),
            Filter::make('expired')->label('Scaduti')->query(fn ($query) => $query->whereDate('expires_at', '<', today())),
            Filter::make('expiring')->label('In scadenza entro 30 giorni')->query(fn ($query) => $query->whereBetween('expires_at', [today(), today()->addDays(30)])),
        ])
            ->recordActions([
                Action::make('download')->label('Scarica')->icon(Heroicon::OutlinedDocumentArrowDown)->action(fn (Document $record) => Storage::disk($record->disk)->download($record->file_path, $record->name)),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDocuments::route('/'), 'create' => CreateDocument::route('/create'), 'edit' => EditDocument::route('/{record}/edit')];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'category', 'contact.first_name', 'contact.last_name', 'company.name', 'practice.title'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Document $record */
        return ['Categoria' => $record->category ?: '-', 'Soggetto' => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) ($record->company?->name ?? '-')];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $query->with(['contact', 'company', 'practice']);
    }
}
