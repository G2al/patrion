<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PracticeStatus;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Practice;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OperationalPracticesWidget extends TableWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected static ?int $sort = 40;

    public function table(Table $table): Table
    {
        $stationary = Practice::query()->whereIn('status', [PracticeStatus::InProgress, PracticeStatus::Waiting])->where('updated_at', '<=', now()->subDays(7))->count();

        return $table->heading("Pratiche operative · {$stationary} ferme")->query(
            Practice::query()->with(['contact', 'company', 'practiceType'])
                ->whereIn('status', [PracticeStatus::InProgress, PracticeStatus::Waiting])
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
                ->orderBy('expected_at')->limit(10)
        )->columns([
            TextColumn::make('internal_number')->label('Codice')->searchable(),
            TextColumn::make('title')->label('Titolo')->limit(30),
            TextColumn::make('subject')->label('Soggetto')->state(fn (Practice $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) $record->company?->name),
            TextColumn::make('practiceType.name')->label('Tipologia'),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRACTICE_STATUSES[$state?->value] ?? '-'),
            TextColumn::make('expected_at')->label('Prevista')->date('d/m/Y')->placeholder('-'),
            TextColumn::make('stationary')->label('Ferma')->badge()->state(fn (Practice $record): string => in_array($record->status, [PracticeStatus::InProgress, PracticeStatus::Waiting], true) && $record->updated_at->lte(now()->subDays(7)) ? 'Sì' : 'No')->color(fn (string $state): string => $state === 'Sì' ? 'danger' : 'gray'),
        ])->recordActions([
            EditAction::make()->url(fn (Practice $record): string => PracticeResource::getUrl('edit', ['record' => $record])),
        ])->paginated(false)->emptyStateHeading('Nessuna pratica operativa');
    }
}
