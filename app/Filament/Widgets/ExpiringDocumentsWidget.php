<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Document;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringDocumentsWidget extends TableWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected static ?int $sort = 70;

    public function table(Table $table): Table
    {
        return $table->heading('Documenti in scadenza')->query(Document::query()->with(['contact', 'company', 'practice'])->where('status', '!=', DocumentStatus::Archived)->whereNotNull('expires_at')->whereDate('expires_at', '<=', today()->addDays(30))->orderBy('expires_at')->limit(10))
            ->columns([
                TextColumn::make('name')->label('Documento')->searchable(),
                TextColumn::make('category')->label('Categoria')->badge(),
                TextColumn::make('subject')->label('Soggetto')->state(fn (Document $record): string => $record->contact ? "{$record->contact->first_name} {$record->contact->last_name}" : (string) ($record->company?->name ?? '-')),
                TextColumn::make('practice.title')->label('Pratica')->placeholder('-'),
                TextColumn::make('expires_at')->label('Scadenza')->date('d/m/Y')->color(fn (Document $record): string => $record->expires_at?->isPast() ? 'danger' : 'warning'),
                TextColumn::make('derived_status')->label('Stato')->badge()->state(fn (Document $record): string => $record->expires_at?->isPast() ? 'Scaduto' : (ItalianOptions::DOCUMENT_STATUSES[$record->status->value] ?? '-'))->color(fn (string $state): string => $state === 'Scaduto' ? 'danger' : 'warning'),
            ])->recordActions([EditAction::make()->url(fn (Document $record): string => DocumentResource::getUrl('edit', ['record' => $record]))])->paginated(false)->emptyStateHeading('Nessun documento in scadenza');
    }
}
