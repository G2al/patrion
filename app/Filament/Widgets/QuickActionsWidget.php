<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Goals\GoalResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Prospects\ProspectResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    protected function getViewData(): array
    {
        return ['actions' => [
            ['label' => 'Nuovo prospect', 'url' => ProspectResource::getUrl('create')],
            ['label' => 'Nuovo cliente', 'url' => ClientResource::getUrl('create')],
            ['label' => 'Nuova azienda', 'url' => CompanyResource::getUrl('create')],
            ['label' => 'Nuovo appuntamento', 'url' => AppointmentResource::getUrl('create')],
            ['label' => 'Nuova attività', 'url' => ActivityResource::getUrl('create')],
            ['label' => 'Nuova pratica', 'url' => PracticeResource::getUrl('create')],
            ['label' => 'Nuovo obiettivo', 'url' => GoalResource::getUrl('create')],
            ['label' => 'Nuovo documento', 'url' => DocumentResource::getUrl('create')],
        ]];
    }
}
