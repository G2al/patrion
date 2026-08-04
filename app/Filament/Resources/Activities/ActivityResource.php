<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities;

use App\Enums\ActivityStatus;
use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Support\ItalianOptions;
use App\Models\Activity;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Attività';

    protected static ?string $modelLabel = 'attività';

    protected static ?string $pluralModelLabel = 'attività';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Attività')->schema([
            TextInput::make('title')->label('Titolo')->required(),
            Select::make('type')->label('Tipologia')->options(ItalianOptions::ACTIVITY_TYPES)->required()->default('general'),
            Textarea::make('description')->label('Descrizione')->columnSpanFull(),
            Select::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable()->preload()->default(request()->integer('contact_id') ?: null),
            Select::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable()->preload()->default(request()->integer('company_id') ?: null),
            Select::make('practice_id')->label('Pratica')->relationship('practice', 'title')->searchable()->preload(),
            Select::make('appointment_id')->label('Appuntamento')->relationship('appointment', 'title')->searchable()->preload(),
            DateTimePicker::make('scheduled_at')->label('Data e ora previste')->seconds(false),
            DateTimePicker::make('due_at')->label('Scadenza')->seconds(false),
            Select::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES)->default('medium')->required(),
            Select::make('status')->label('Stato')->options(ItalianOptions::ACTIVITY_STATUSES)->default('pending')->required(),
            Textarea::make('outcome')->label('Esito'),
            Textarea::make('notes')->label('Note'),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contact', 'company']))->defaultSort('due_at')->columns([
            TextColumn::make('title')->label('Titolo')->searchable(),
            TextColumn::make('type')->label('Tipologia')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::ACTIVITY_TYPES[$state?->value] ?? '-'),
            TextColumn::make('contact.last_name')->label('Contatto'), TextColumn::make('company.name')->label('Azienda'),
            TextColumn::make('due_at')->label('Scadenza')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-'),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::ACTIVITY_STATUSES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
        ])->filters([
            SelectFilter::make('type')->label('Tipologia')->options(ItalianOptions::ACTIVITY_TYPES),
            SelectFilter::make('status')->label('Stato')->options(ItalianOptions::ACTIVITY_STATUSES),
            SelectFilter::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES),
            Filter::make('overdue')->label('Scadute')->query(fn (Builder $query): Builder => $query->open()->where('due_at', '<', now())),
            Filter::make('period')->label('Periodo scadenza')->schema([DateTimePicker::make('from')->label('Dal'), DateTimePicker::make('until')->label('Al')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->where('due_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->where('due_at', '<=', $date))),
        ])->recordActions([
            Action::make('complete')->label('Segna completata')->icon(Heroicon::OutlinedClipboardDocumentCheck)->color('success')->requiresConfirmation()->visible(fn (Activity $record): bool => $record->status !== ActivityStatus::Completed)->action(function (Activity $record): void {
                $record->update(['status' => ActivityStatus::Completed]);
                Notification::make()->success()->title('Attività completata')->send();
            }),
            Action::make('postpone')->label('Rinvia')->schema([DateTimePicker::make('due_at')->label('Nuova scadenza')->required()->after('now')])->action(fn (Activity $record, array $data): bool => $record->update(['status' => ActivityStatus::Postponed, 'due_at' => $data['due_at']])),
            Action::make('duplicate')->label('Duplica')->icon(Heroicon::OutlinedDocumentDuplicate)->action(function (Activity $record): void {
                $copy = $record->replicate(['completed_at']);
                $copy->title = "Copia di {$record->title}";
                $copy->status = ActivityStatus::Pending;
                $copy->completed_at = null;
                $copy->save();
                Notification::make()->success()->title('Attività duplicata')->send();
            }),
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/'), 'create' => CreateActivity::route('/create'), 'edit' => EditActivity::route('/{record}/edit')];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Activity::query()->open()->where('due_at', '<', now())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
