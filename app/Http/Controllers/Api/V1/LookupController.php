<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Priority;
use App\Filament\Support\ItalianOptions;
use App\Models\PracticeType;

final class LookupController extends ApiController
{
    public function __invoke()
    {
        return $this->ok([
            'practice_types' => PracticeType::query()->active()->ordered()->get(['id', 'name', 'slug', 'color']),
            'priorities' => ItalianOptions::PRIORITIES,
            'activity_types' => ItalianOptions::ACTIVITY_TYPES,
            'activity_statuses' => ItalianOptions::ACTIVITY_STATUSES,
            'appointment_statuses' => ItalianOptions::APPOINTMENT_STATUSES,
            'appointment_outcomes' => ItalianOptions::APPOINTMENT_OUTCOMES,
            'appointment_modes' => ItalianOptions::APPOINTMENT_MODES,
            'practice_statuses' => ItalianOptions::PRACTICE_STATUSES,
            'document_statuses' => ItalianOptions::DOCUMENT_STATUSES,
            'goal_statuses' => ItalianOptions::GOAL_STATUSES,
            'priorities_enum' => array_column(Priority::cases(), 'value'),
        ]);
    }
}
