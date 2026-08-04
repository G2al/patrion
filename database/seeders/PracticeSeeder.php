<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PracticeStatus;
use App\Enums\Priority;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Practice;
use App\Models\PracticeType;

final class PracticeSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $types = PracticeType::query()->active()->ordered()->get()->keyBy('slug');
        $contacts = Contact::query()->whereIn('email', ['luigi.iommelli@example.test', 'giulia.bianchi@example.test', 'alessandra.costantini@example.test', 'davide.moretti@example.test'])->get()->keyBy('email');
        $companies = Company::query()->get()->keyBy('vat_number');

        $practices = [
            ['number' => 'PR-2026-001', 'title' => 'Piano di accumulo familiare', 'type' => 'pac', 'contact' => 'luigi.iommelli@example.test', 'status' => PracticeStatus::Completed, 'priority' => Priority::High, 'opened_at' => now()->subMonths(3), 'completed_at' => today()->subDays(2), 'expected_value' => 18000, 'actual_value' => 18000, 'outcome' => 'Cliente acquisito'],
            ['number' => 'PR-2026-002', 'title' => 'Revisione protezione familiare', 'type' => 'patrimoniale', 'contact' => 'giulia.bianchi@example.test', 'status' => PracticeStatus::InProgress, 'priority' => Priority::Medium, 'opened_at' => now()->subDays(18), 'expected_at' => now()->addDays(12), 'expected_value' => 12000],
            ['number' => 'PR-2026-003', 'title' => 'Analisi previdenziale', 'type' => 'gestione-separata', 'contact' => 'alessandra.costantini@example.test', 'status' => PracticeStatus::Waiting, 'priority' => Priority::High, 'opened_at' => now()->subDays(10), 'expected_at' => now()->addDays(20), 'expected_value' => 15000, 'notes' => 'In attesa dei documenti richiesti al prospect.'],
            ['number' => 'PR-2026-004', 'title' => 'Copertura aziendale', 'type' => 'patrimoniale', 'company' => 'IT09123456781', 'status' => PracticeStatus::Draft, 'priority' => Priority::Low, 'opened_at' => now()->subDays(4), 'expected_at' => now()->addDays(30), 'expected_value' => 25000],
        ];

        foreach ($practices as $data) {
            Practice::query()->updateOrCreate(['internal_number' => $data['number']], [
                'title' => $data['title'], 'practice_type_id' => $types[$data['type']]->id, 'contact_id' => isset($data['contact']) ? $contacts[$data['contact']]->id : null, 'company_id' => isset($data['company']) ? $companies[$data['company']]->id : null,
                'status' => $data['status'], 'priority' => $data['priority'], 'opened_at' => $data['opened_at'], 'expected_at' => $data['expected_at'] ?? null, 'completed_at' => $data['completed_at'] ?? null, 'expected_value' => $data['expected_value'] ?? null, 'actual_value' => $data['actual_value'] ?? null, 'outcome' => $data['outcome'] ?? null, 'notes' => $data['notes'] ?? null, 'owner_id' => $owner->id,
            ]);
        }
    }
}
