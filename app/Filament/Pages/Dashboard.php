<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ActiveGoalsWidget;
use App\Filament\Widgets\DueActivitiesWidget;
use App\Filament\Widgets\ExpiringDocumentsWidget;
use App\Filament\Widgets\OperationalPracticesWidget;
use App\Filament\Widgets\OperationalStatsWidget;
use App\Filament\Widgets\ProspectsToContactWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentTimelineWidget;
use App\Filament\Widgets\TodayAppointmentsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Centro operativo';

    public function getTitle(): string|Htmlable
    {
        return 'Centro operativo';
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    public function getWidgets(): array
    {
        return [
            OperationalStatsWidget::class,
            QuickActionsWidget::class,
            TodayAppointmentsWidget::class,
            DueActivitiesWidget::class,
            OperationalPracticesWidget::class,
            ActiveGoalsWidget::class,
            ProspectsToContactWidget::class,
            ExpiringDocumentsWidget::class,
            RecentTimelineWidget::class,
        ];
    }
}
