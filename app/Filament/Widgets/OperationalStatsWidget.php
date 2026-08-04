<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\ContactStatus;
use App\Enums\GoalStatus;
use App\Enums\PracticeStatus;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Goals\GoalResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\Practice;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Riepilogo generale';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $openStatuses = [ActivityStatus::Pending, ActivityStatus::InProgress, ActivityStatus::Postponed];

        return [
            Stat::make('Appuntamenti di oggi', Appointment::query()->whereDate('starts_at', today())->count())->icon(Heroicon::OutlinedCalendarDays)->color('info')->url(AppointmentResource::getUrl()),
            Stat::make('Attività da completare', Activity::query()->whereIn('status', $openStatuses)->count())->icon(Heroicon::OutlinedClipboardDocumentList)->color('warning')->url(ActivityResource::getUrl()),
            Stat::make('Follow-up scaduti', Activity::query()->where('type', ActivityType::FollowUp)->whereIn('status', $openStatuses)->where('due_at', '<', now())->count())->icon(Heroicon::OutlinedClock)->color('danger')->url(ActivityResource::getUrl()),
            Stat::make('Pratiche in lavorazione', Practice::query()->where('status', PracticeStatus::InProgress)->count())->icon(Heroicon::OutlinedBriefcase)->color('info')->url(PracticeResource::getUrl()),
            Stat::make('Pratiche in attesa', Practice::query()->where('status', PracticeStatus::Waiting)->count())->icon(Heroicon::OutlinedPauseCircle)->color('warning')->url(PracticeResource::getUrl()),
            Stat::make('Prospect attivi', Contact::query()->where('status', ContactStatus::Prospect)->count())->icon(Heroicon::OutlinedUserPlus)->url(ProspectResource::getUrl()),
            Stat::make('Clienti totali', Contact::query()->where('status', ContactStatus::Client)->count())->icon(Heroicon::OutlinedUserGroup)->color('success')->url(ClientResource::getUrl()),
            Stat::make('Obiettivi attivi', Goal::query()->where('status', GoalStatus::Active)->count())->icon(Heroicon::OutlinedFlag)->color('success')->url(GoalResource::getUrl()),
        ];
    }
}
