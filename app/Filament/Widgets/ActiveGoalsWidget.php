<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\GoalStatus;
use App\Filament\Resources\Goals\GoalResource;
use App\Models\Goal;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveGoalsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 50;

    public function table(Table $table): Table
    {
        return $table->heading('Obiettivi attivi')->query(Goal::query()->with('practiceType')->where('status', GoalStatus::Active)->orderBy('ends_at'))
            ->columns([
                TextColumn::make('title')->label('Obiettivo')->searchable(),
                TextColumn::make('practiceType.name')->label('Tipologia'),
                ViewColumn::make('progress')->label('Progresso')->view('filament.tables.columns.goal-progress'),
                TextColumn::make('visual_status')->label('Valutazione')->badge()->state(fn (Goal $record): string => self::visualStatus($record))->color(fn (string $state): string => match ($state) {
                    'Raggiunto', 'In linea' => 'success', 'A rischio' => 'warning', 'Scaduto' => 'danger', default => 'gray'
                }),
                TextColumn::make('ends_at')->label('Scadenza')->date('d/m/Y')->sortable(),
            ])->recordActions([EditAction::make()->url(fn (Goal $record): string => GoalResource::getUrl('edit', ['record' => $record]))])->paginated(false)->emptyStateHeading('Nessun obiettivo attivo');
    }

    public static function visualStatus(Goal $goal): string
    {
        if ($goal->progress_percentage >= 100) {
            return 'Raggiunto';
        }

        if ($goal->ends_at->isPast()) {
            return 'Scaduto';
        }

        $totalDays = max(1, $goal->starts_at->diffInDays($goal->ends_at));
        $elapsed = min($totalDays, max(0, $goal->starts_at->diffInDays(today(), false)));
        $expectedProgress = ($elapsed / $totalDays) * 100;

        return $goal->progress_percentage + 15 < $expectedProgress || ($goal->ends_at->diffInDays(today()) <= 7 && $goal->progress_percentage < 75)
            ? 'A rischio'
            : 'In linea';
    }
}
