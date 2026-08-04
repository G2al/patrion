<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ActivityStatus;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DueActivitiesWidget extends TableWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected static ?int $sort = 30;

    public function table(Table $table): Table
    {
        return $table->heading('Attività e follow-up')->query(
            Activity::query()->with(['contact', 'company'])->open()->whereNotNull('due_at')->where('due_at', '<=', now()->addDays(7))
                ->orderByRaw('CASE WHEN due_at < ? THEN 0 ELSE 1 END', [now()])
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
                ->orderBy('due_at')->limit(10)
        )->columns([
            TextColumn::make('title')->label('Attività')->searchable(),
            TextColumn::make('type')->label('Tipo')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::ACTIVITY_TYPES[$state?->value] ?? '-'),
            TextColumn::make('subject')->label('Soggetto')->state(fn (Activity $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) ($record->company?->name ?? '-')),
            TextColumn::make('due_at')->label('Scadenza')->dateTime('d/m H:i')->color(fn (Activity $record): string => $record->due_at?->isPast() ? 'danger' : 'gray'),
            TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-'),
        ])->recordActions([
            EditAction::make()->url(fn (Activity $record): string => ActivityResource::getUrl('edit', ['record' => $record])),
            Action::make('complete')->label('Completa')->icon(Heroicon::OutlinedCheck)->color('success')->requiresConfirmation()->action(function (Activity $record): void {
                $record->update(['status' => ActivityStatus::Completed]);
                Notification::make()->success()->title('Attività completata')->send();
            }),
        ])->paginated(false)->emptyStateHeading('Nessuna attività urgente');
    }
}
