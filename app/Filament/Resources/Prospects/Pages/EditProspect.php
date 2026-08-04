<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProspect extends EditRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), ProspectResource::convertAction(), DeleteAction::make()];
    }
}
