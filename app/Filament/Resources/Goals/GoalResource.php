<?php

declare(strict_types=1);

namespace App\Filament\Resources\Goals;

use App\Filament\Resources\Goals\Pages\CreateGoal;
use App\Filament\Resources\Goals\Pages\EditGoal;
use App\Filament\Resources\Goals\Pages\ListGoals;
use App\Filament\Support\ItalianOptions;
use App\Models\Goal;
use App\Models\PracticeType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Obiettivi';

    protected static ?string $modelLabel = 'obiettivo';

    protected static ?string $pluralModelLabel = 'obiettivi';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Obiettivo')->schema([
            TextInput::make('title')->label('Titolo')->required(), Textarea::make('description')->label('Descrizione')->columnSpanFull(),
            Select::make('practice_type_id')->label('Tipologia pratica')->options(fn (): array => PracticeType::query()->ordered()->pluck('name', 'id')->all())->required()->searchable(),
            TextInput::make('target_quantity')->label('Quantità target')->numeric()->integer()->minValue(1)->required(),
            DatePicker::make('starts_at')->label('Data iniziale')->required(), DatePicker::make('ends_at')->label('Data finale')->required()->afterOrEqual('starts_at'),
            Select::make('status')->label('Stato')->options(ItalianOptions::GOAL_STATUSES)->default('active')->required(),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Obiettivo')->searchable(), TextColumn::make('practiceType.name')->label('Tipologia'),
            ViewColumn::make('progress')->label('Progresso')->view('filament.tables.columns.goal-progress'),
            TextColumn::make('starts_at')->label('Dal')->date('d/m/Y'), TextColumn::make('ends_at')->label('Al')->date('d/m/Y'),
            TextColumn::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::GOAL_STATUSES[$state?->value] ?? '-'),
        ])->filters([
            SelectFilter::make('status')->label('Stato')->options(ItalianOptions::GOAL_STATUSES),
            SelectFilter::make('practice_type_id')->label('Tipologia')->relationship('practiceType', 'name'),
            Filter::make('period')->label('Periodo')->schema([DatePicker::make('from')->label('Dal'), DatePicker::make('until')->label('Al')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('ends_at', '<=', $date))),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListGoals::route('/'), 'create' => CreateGoal::route('/create'), 'edit' => EditGoal::route('/{record}/edit')];
    }
}
