<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts;

use App\Filament\Support\ItalianOptions;
use App\Models\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ContactResourceTable
{
    public static function configure(Table $table, bool $prospects): Table
    {
        $columns = [
            ImageColumn::make('photo_path')->label('Foto')->disk('local')->circular(),
            TextColumn::make('initials')->label('Iniziali')->state(fn (Contact $record): string => mb_strtoupper(mb_substr($record->first_name, 0, 1).mb_substr($record->last_name, 0, 1)))->badge()->color('gray'),
            TextColumn::make('full_name')->label('Nome e cognome')->state(fn (Contact $record): string => "{$record->first_name} {$record->last_name}")->searchable(['first_name', 'last_name', 'email', 'phone', 'whatsapp', 'tax_code'])->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('last_name', $direction)->orderBy('first_name', $direction)),
        ];

        if ($prospects) {
            $columns = [...$columns,
                TextColumn::make('source')->label('Provenienza')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::SOURCES[$state?->value] ?? '-'),
                TextColumn::make('interests')->label('Interessi')->badge()->formatStateUsing(fn (string $state): string => ItalianOptions::INTERESTS[$state] ?? $state),
                TextColumn::make('potential_value')->label('Potenziale')->money('EUR')->sortable(),
                TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
                TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn (): string => 'Prospect'),
                TextColumn::make('next_follow_up_at')->label('Prossimo follow-up')->dateTime('d/m/Y H:i')->sortable(),
            ];
        } else {
            $columns = [...$columns,
                TextColumn::make('phone')->label('Telefono')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('profession')->label('Professione')->searchable()->toggleable(),
                TextColumn::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-')->color(fn ($state): string => ItalianOptions::badgeColor($state?->value)),
                TextColumn::make('last_contact_at')->label('Ultimo contatto')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('next_follow_up_at')->label('Prossimo follow-up')->dateTime('d/m/Y H:i')->sortable(),
            ];
        }

        $filters = [
            SelectFilter::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES),
            SelectFilter::make('source')->label('Provenienza')->options(ItalianOptions::SOURCES),
            Filter::make('follow_up')->label('Prossimo follow-up')->schema([
                DatePicker::make('from')->label('Dal'),
                DatePicker::make('until')->label('Al'),
            ])->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('next_follow_up_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('next_follow_up_at', '<=', $date))),
            Filter::make('interest')->label('Interesse')->schema([
                Select::make('value')->label('Interesse')->options(ItalianOptions::INTERESTS),
            ])->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $interest): Builder => $query->whereJsonContains('interests', $interest))),
        ];

        if ($prospects) {
            $filters[] = Filter::make('without_future_appointment')->label('Senza appuntamento futuro')->query(fn (Builder $query): Builder => $query->whereDoesntHave('appointments', fn (Builder $query): Builder => $query->where('starts_at', '>', now())));
        } else {
            $filters[] = SelectFilter::make('profession')->label('Professione')->options(fn (): array => Contact::query()->clients()->whereNotNull('profession')->distinct()->pluck('profession', 'profession')->all())->searchable();
            $filters[] = SelectFilter::make('relationship_level')->label('Livello relazione')->options(fn (): array => Contact::query()->clients()->whereNotNull('relationship_level')->distinct()->pluck('relationship_level', 'relationship_level')->all());
        }

        return $table
            ->columns($columns)
            ->filters($filters);
    }
}
