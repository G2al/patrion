<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Practices\PracticeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class PracticesRelationManager extends RelationManager
{
    protected static string $relationship = 'practices';

    protected static ?string $relatedResource = PracticeResource::class;

    protected static ?string $title = 'Pratiche';

    public function table(Table $table): Table
    {
        return $table->headerActions([
            CreateAction::make()->label('Nuova pratica')->mutateDataUsing(fn (array $data): array => [...$data, 'owner_id' => auth()->id()]),
        ]);
    }
}
