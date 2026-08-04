<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Appointment;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TodayAppointmentsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Appuntamenti di oggi')
            ->query(Appointment::query()->with(['contact', 'company', 'practice'])->whereDate('starts_at', today())->orderBy('starts_at'))
            ->columns([
                TextColumn::make('starts_at')->label('Ora')->time('H:i')->sortable(),
                TextColumn::make('title')->label('Titolo')->searchable(),
                TextColumn::make('subject')->label('Soggetto')->state(fn (Appointment $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name),
                TextColumn::make('mode')->label('Modalità')->formatStateUsing(fn (?string $state): string => ItalianOptions::APPOINTMENT_MODES[$state] ?? '-'),
                TextColumn::make('display_status')->label('Stato')->badge()->state(fn (Appointment $record): string => $record->status === AppointmentStatus::Scheduled && $record->ends_at->isPast() ? 'Da consuntivare' : (ItalianOptions::APPOINTMENT_STATUSES[$record->status->value] ?? $record->status->value))->color(fn (Appointment $record): string => $record->status === AppointmentStatus::Scheduled && $record->ends_at->isPast() ? 'danger' : 'gray'),
                TextColumn::make('practice.title')->label('Pratica')->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Appointment $record): string => AppointmentResource::getUrl('view', ['record' => $record])),
                EditAction::make()->url(fn (Appointment $record): string => AppointmentResource::getUrl('edit', ['record' => $record])),
                AppointmentResource::reportAction(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessun appuntamento oggi');
    }
}
