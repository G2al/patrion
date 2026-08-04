<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments;

use App\Actions\ReportAppointment;
use App\Data\ReportAppointmentData;
use App\Enums\AppointmentOutcome;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Support\ItalianOptions;
use App\Models\Appointment;
use App\Models\PracticeType;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Appuntamenti';

    protected static ?string $modelLabel = 'appuntamento';

    protected static ?string $pluralModelLabel = 'appuntamenti';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appuntamento')->schema([
                TextInput::make('title')->label('Titolo')->required(),
                Textarea::make('description')->label('Descrizione')->columnSpanFull(),
                Select::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable()->preload()->live()->default(request()->integer('contact_id') ?: null)->required(fn (Get $get): bool => blank($get('company_id')))->disabled(fn (Get $get): bool => filled($get('company_id'))),
                Select::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable()->preload()->live()->default(request()->integer('company_id') ?: null)->required(fn (Get $get): bool => blank($get('contact_id')))->disabled(fn (Get $get): bool => filled($get('contact_id'))),
                Select::make('practice_id')->label('Pratica')->relationship('practice', 'title')->searchable()->preload(),
                DateTimePicker::make('starts_at')->label('Inizio')->seconds(false)->required()->default(now()->addHour()->startOfHour()),
                DateTimePicker::make('ends_at')->label('Fine')->seconds(false)->required()->after('starts_at')->default(now()->addHours(2)->startOfHour()),
                TextInput::make('location')->label('Luogo'),
                Select::make('mode')->label('Modalità')->options(ItalianOptions::APPOINTMENT_MODES),
                Select::make('status')->label('Stato')->options(ItalianOptions::APPOINTMENT_STATUSES)->default('scheduled')->required(),
                Select::make('outcome')->label('Esito')->options(ItalianOptions::APPOINTMENT_OUTCOMES),
                Textarea::make('final_notes')->label('Note finali')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contact', 'company']))->defaultSort('starts_at')->columns([
            TextColumn::make('starts_at')->label('Inizio')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('title')->label('Titolo')->searchable(),
            TextColumn::make('subject')->label('Soggetto')->state(fn (Appointment $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name),
            TextColumn::make('mode')->label('Modalità')->formatStateUsing(fn (?string $state): string => ItalianOptions::APPOINTMENT_MODES[$state] ?? '-'),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::APPOINTMENT_STATUSES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
            TextColumn::make('outcome')->label('Esito')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::APPOINTMENT_OUTCOMES[$state?->value] ?? '-'),
        ])->filters([
            Filter::make('period')->label('Periodo')->schema([DatePicker::make('from')->label('Dal'), DatePicker::make('until')->label('Al')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date))),
            SelectFilter::make('status')->label('Stato')->options(ItalianOptions::APPOINTMENT_STATUSES),
            SelectFilter::make('outcome')->label('Esito')->options(ItalianOptions::APPOINTMENT_OUTCOMES),
            SelectFilter::make('mode')->label('Modalità')->options(ItalianOptions::APPOINTMENT_MODES),
            SelectFilter::make('contact_id')->label('Contatto')->relationship('contact', 'last_name')->searchable(),
            SelectFilter::make('company_id')->label('Azienda')->relationship('company', 'name')->searchable(),
        ])->recordActions([static::reportAction(), ViewAction::make(), EditAction::make()]);
    }

    public static function reportAction(): Action
    {
        return Action::make('report')->label('Consuntiva appuntamento')->icon(Heroicon::OutlinedClipboardDocumentCheck)->color('success')
            ->schema([
                Toggle::make('occurred')->label('L’appuntamento si è svolto?')->default(true)->live(),
                Select::make('outcome')->label('Qual è stato l’esito?')->options(ItalianOptions::APPOINTMENT_OUTCOMES)->live(),
                Textarea::make('emerged_need')->label('Quale esigenza è emersa?'),
                Toggle::make('prospect_interested')->label('Il prospect è interessato?')->visible(fn (Get $get): bool => (bool) $get('occurred')),
                Toggle::make('convert_to_client')->label('Vuoi convertirlo in cliente?')->visible(fn (Get $get): bool => (bool) $get('prospect_interested')),
                Toggle::make('open_practice')->label('Vuoi aprire una pratica?')->live(),
                Select::make('practice_type_id')->label('Tipologia di pratica')->options(fn (): array => PracticeType::query()->active()->ordered()->pluck('name', 'id')->all())->required(fn (Get $get): bool => (bool) $get('open_practice'))->visible(fn (Get $get): bool => (bool) $get('open_practice')),
                Toggle::make('follow_up_required')->label('È necessario un follow-up?')->live(),
                DateTimePicker::make('next_contact_at')->label('Data del prossimo contatto')->seconds(false)->required(fn (Get $get): bool => (bool) $get('follow_up_required'))->visible(fn (Get $get): bool => (bool) $get('follow_up_required')),
                Select::make('negative_reason')->label('Motivazione dell’esito negativo')->options(ItalianOptions::NEGATIVE_REASONS)->visible(fn (Get $get): bool => $get('outcome') === 'negative'),
                Textarea::make('notes')->label('Note sull’incontro'),
            ])->action(function (Appointment $record, array $data): void {
                app(ReportAppointment::class)->handle($record, new ReportAppointmentData(
                    occurred: (bool) $data['occurred'],
                    outcome: filled($data['outcome'] ?? null) ? AppointmentOutcome::from($data['outcome']) : null,
                    emergedNeed: $data['emerged_need'] ?? null,
                    prospectInterested: $data['prospect_interested'] ?? null,
                    convertToClient: (bool) ($data['convert_to_client'] ?? false),
                    openPractice: (bool) ($data['open_practice'] ?? false),
                    practiceTypeId: $data['practice_type_id'] ?? null,
                    followUpRequired: (bool) ($data['follow_up_required'] ?? false),
                    nextContactAt: filled($data['next_contact_at'] ?? null) ? CarbonImmutable::parse($data['next_contact_at']) : null,
                    negativeReason: $data['negative_reason'] ?? null,
                    notes: $data['notes'] ?? null,
                ), (int) auth()->id());
                Notification::make()->success()->title('Appuntamento consuntivato')->send();
            });
    }

    public static function getPages(): array
    {
        return ['index' => ListAppointments::route('/'), 'create' => CreateAppointment::route('/create'), 'view' => ViewAppointment::route('/{record}'), 'edit' => EditAppointment::route('/{record}/edit')];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Appuntamento')->schema([
            TextEntry::make('title')->label('Titolo'),
            TextEntry::make('starts_at')->label('Inizio')->dateTime('d/m/Y H:i'),
            TextEntry::make('ends_at')->label('Fine')->dateTime('d/m/Y H:i'),
            TextEntry::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::APPOINTMENT_STATUSES[$state?->value] ?? '-'),
            TextEntry::make('outcome')->label('Esito')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::APPOINTMENT_OUTCOMES[$state?->value] ?? '-'),
            TextEntry::make('emerged_need')->label('Esigenza emersa')->columnSpanFull(),
            TextEntry::make('final_notes')->label('Note finali')->columnSpanFull(),
        ])->columns(2)]);
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class, NotesRelationManager::class];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Appointment::query()->whereDate('starts_at', today())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'contact.first_name', 'contact.last_name', 'company.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Appointment $record */
        return ['Soggetto' => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name, 'Data' => $record->starts_at->format('d/m/Y H:i')];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $query->with(['contact', 'company']);
    }
}
