<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $relatedResource = DocumentResource::class;

    protected static ?string $title = 'Documenti';

    public function table(Table $table): Table
    {
        return $table->headerActions([
            CreateAction::make()->label('Nuovo documento')->mutateDataUsing(fn (array $data): array => [...$data, 'disk' => 'local', 'uploaded_by_id' => auth()->id()]),
        ]);
    }
}
