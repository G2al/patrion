<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Enums\Priority;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Contact;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ProspectsToContactWidget extends TableWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected static ?int $sort = 60;

    public function table(Table $table): Table
    {
        return $table->heading('Prospect da ricontattare')->query(
            Contact::query()->where('status', ContactStatus::Prospect)->where(function (Builder $query): void {
                $query->whereDate('next_follow_up_at', '<=', today())
                    ->orWhereNull('last_contact_at')
                    ->orWhere('priority', Priority::High)
                    ->orWhereDoesntHave('appointments', fn (Builder $query): Builder => $query->where('starts_at', '>', now()));
            })->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")->orderBy('next_follow_up_at')->limit(10)
        )->columns([
            TextColumn::make('full_name')->label('Prospect')->state(fn (Contact $record): string => "{$record->first_name} {$record->last_name}"),
            TextColumn::make('source')->label('Provenienza')->formatStateUsing(fn ($state): string => ItalianOptions::SOURCES[$state?->value] ?? '-'),
            TextColumn::make('interests')->label('Interessi')->badge()->formatStateUsing(fn (string $state): string => ItalianOptions::INTERESTS[$state] ?? $state),
            TextColumn::make('potential_value')->label('Potenziale')->money('EUR'),
            TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-'),
            TextColumn::make('next_follow_up_at')->label('Follow-up')->dateTime('d/m H:i')->color(fn (Contact $record): string => $record->next_follow_up_at?->isPast() ? 'danger' : 'gray')->placeholder('Mai pianificato'),
        ])->recordActions([
            ViewAction::make()->url(fn (Contact $record): string => ProspectResource::getUrl('view', ['record' => $record])),
            Action::make('appointment')->label('Appuntamento')->icon(Heroicon::OutlinedCalendarDays)->url(fn (Contact $record): string => AppointmentResource::getUrl('create', ['contact_id' => $record->id])),
            Action::make('activity')->label('Attività')->icon(Heroicon::OutlinedClipboardDocumentList)->url(fn (Contact $record): string => ActivityResource::getUrl('create', ['contact_id' => $record->id])),
        ])->paginated(false)->emptyStateHeading('Nessun prospect da ricontattare');
    }
}
