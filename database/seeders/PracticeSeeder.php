<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PracticeStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Practice;
use App\Models\PracticeType;

class PracticeSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $types = PracticeType::query()->orderBy('sort_order')->get();
        $pac = $types->firstWhere('slug', 'pac');

        foreach (range(1, 40) as $index) {
            $number = sprintf('DEMO-PR-%03d', $index);
            if (Practice::query()->where('internal_number', $number)->exists()) {
                continue;
            }

            $status = match (true) {
                $index <= 13 => PracticeStatus::Completed,
                $index <= 20 => PracticeStatus::InProgress,
                $index <= 27 => PracticeStatus::Waiting,
                $index <= 32 => PracticeStatus::Draft,
                $index <= 36 => PracticeStatus::Unsuccessful,
                default => PracticeStatus::Cancelled,
            };
            $type = $index <= 8 ? $pac : $types[($index - 1) % $types->count()];
            $companySubject = $index % 5 === 0;
            $openedAt = today()->subDays(15 + $index);
            $completedAt = $status === PracticeStatus::Completed ? today()->subDays($index % max(1, today()->day)) : null;

            Practice::factory()->create([
                'internal_number' => $number, 'title' => "Pratica demo {$index}",
                'practice_type_id' => $type->id, 'contact_id' => $companySubject ? null : $contacts[($index - 1) % $contacts->count()]->id,
                'company_id' => $companySubject ? $companies[($index - 1) % $companies->count()]->id : null,
                'status' => $status, 'opened_at' => $openedAt, 'expected_at' => today()->addDays(($index % 30) + 1),
                'completed_at' => $completedAt, 'owner_id' => $owner->id,
                'updated_at' => in_array($status, [PracticeStatus::InProgress, PracticeStatus::Waiting], true) && $index % 2 === 0 ? now()->subDays(10) : now(),
            ]);
        }
    }
}
