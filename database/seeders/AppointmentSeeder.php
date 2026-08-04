<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentOutcome;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Practice;

class AppointmentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $practices = Practice::query()->where('internal_number', 'like', 'DEMO-PR-%')->orderBy('id')->get();

        foreach (range(1, 35) as $index) {
            $title = sprintf('DEMO Appuntamento %02d', $index);
            if (Appointment::query()->where('title', $title)->exists()) {
                continue;
            }

            $startsAt = match (true) {
                $index <= 5 => today()->setTime(8 + $index, 0),
                $index <= 10 => today()->setTime(9 + ($index - 5), 30),
                default => now()->addDays(($index % 21) - 10)->setTime(9 + ($index % 8), 0),
            };
            $status = match (true) {
                $index <= 5 => AppointmentStatus::Scheduled,
                $index <= 10 => AppointmentStatus::Completed,
                $index % 9 === 0 => AppointmentStatus::Cancelled,
                $index % 7 === 0 => AppointmentStatus::NoShow,
                default => AppointmentStatus::Scheduled,
            };
            $companySubject = $index % 6 === 0;

            Appointment::factory()->create([
                'title' => $title, 'contact_id' => $companySubject ? null : $contacts[($index - 1) % $contacts->count()]->id,
                'company_id' => $companySubject ? $companies[($index - 1) % $companies->count()]->id : null,
                'practice_id' => $index % 3 === 0 ? $practices[($index - 1) % $practices->count()]->id : null,
                'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addHour(), 'status' => $status,
                'outcome' => $status === AppointmentStatus::Completed ? ($index % 4 === 0 ? AppointmentOutcome::Negative : AppointmentOutcome::Positive) : null,
                'reported_at' => $status === AppointmentStatus::Completed ? $startsAt->copy()->addHours(2) : null,
                'owner_id' => $owner->id,
            ]);
        }
    }
}
