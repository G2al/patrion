<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Practice;

class ActivitySeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $practices = Practice::query()->where('internal_number', 'like', 'DEMO-PR-%')->orderBy('id')->get();
        $appointments = Appointment::query()->where('title', 'like', 'DEMO Appuntamento %')->orderBy('id')->get();

        foreach (range(1, 50) as $index) {
            $title = sprintf('DEMO Attività %02d', $index);
            if (Activity::query()->where('title', $title)->exists()) {
                continue;
            }

            $status = $index % 5 === 0 ? ActivityStatus::Completed : ($index % 7 === 0 ? ActivityStatus::InProgress : ActivityStatus::Pending);
            $type = $index % 3 === 0 ? ActivityType::FollowUp : ActivityType::cases()[$index % count(ActivityType::cases())];
            $dueAt = match (true) {
                $index <= 12 => now()->subDays(($index % 5) + 1), $index <= 24 => today()->setTime(10 + ($index % 6), 0), default => now()->addDays(($index % 14) + 1)
            };
            $companySubject = $index % 8 === 0;

            Activity::factory()->create([
                'title' => $title, 'type' => $type,
                'contact_id' => $companySubject ? null : $contacts[($index - 1) % $contacts->count()]->id,
                'company_id' => $companySubject ? $companies[($index - 1) % $companies->count()]->id : null,
                'practice_id' => $index % 4 === 0 ? $practices[($index - 1) % $practices->count()]->id : null,
                'appointment_id' => $index % 6 === 0 ? $appointments[($index - 1) % $appointments->count()]->id : null,
                'scheduled_at' => $dueAt->copy()->subHour(), 'due_at' => $dueAt,
                'priority' => $index % 6 === 0 ? Priority::High : Priority::Medium, 'status' => $status,
                'completed_at' => $status === ActivityStatus::Completed ? now()->subDays($index % 4) : null, 'owner_id' => $owner->id,
            ]);
        }
    }
}
