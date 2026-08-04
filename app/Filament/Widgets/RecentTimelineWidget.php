<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Company;
use App\Models\Contact;
use App\Models\TimelineEvent;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentTimelineWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 80;

    public function table(Table $table): Table
    {
        return $table->heading('Attività recente')->query(TimelineEvent::query()->with(['subject', 'author'])->latest('occurred_at')->limit(10))
            ->columns([
                TextColumn::make('title')->label('Evento')->weight('bold')->searchable(),
                TextColumn::make('description')->label('Descrizione')->limit(80)->placeholder('-'),
                TextColumn::make('subject_name')->label('Soggetto')->state(fn (TimelineEvent $record): string => $record->subject instanceof Contact ? "{$record->subject->first_name} {$record->subject->last_name}" : (string) $record->subject?->name),
                TextColumn::make('occurred_at')->label('Data e ora')->since()->dateTimeTooltip('d/m/Y H:i'),
                TextColumn::make('author.name')->label('Autore')->placeholder('Sistema'),
            ])->recordActions([
                ViewAction::make()->url(fn (TimelineEvent $record): string => $this->subjectUrl($record)),
            ])->paginated(false)->emptyStateHeading('Nessuna attività recente');
    }

    private function subjectUrl(TimelineEvent $event): string
    {
        if ($event->subject instanceof Company) {
            return CompanyResource::getUrl('view', ['record' => $event->subject]);
        }

        if ($event->subject instanceof Contact) {
            $resource = $event->subject->status->value === 'client' ? ClientResource::class : ProspectResource::class;

            return $resource::getUrl('view', ['record' => $event->subject]);
        }

        return '#';
    }
}
