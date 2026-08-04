<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Practice;

final class ActivitySeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        $practices = Practice::query()->get()->keyBy('internal_number');
        $appointments = Appointment::query()->get()->keyBy('title');
        $activities = [
            ['title' => 'Inviare riepilogo revisione portafoglio', 'type' => ActivityType::Email, 'contact' => 'luigi.iommelli@example.test', 'practice' => 'PR-2026-001', 'due_at' => today()->addDays(2)->setTime(12, 0), 'status' => ActivityStatus::Pending, 'priority' => Priority::Medium],
            ['title' => 'Raccogliere documenti previdenziali', 'type' => ActivityType::DocumentRequest, 'contact' => 'alessandra.costantini@example.test', 'practice' => 'PR-2026-003', 'due_at' => today()->addDay()->setTime(17, 0), 'status' => ActivityStatus::InProgress, 'priority' => Priority::High],
            ['title' => 'Preparare proposta protezione', 'type' => ActivityType::PracticeReview, 'contact' => 'giulia.bianchi@example.test', 'practice' => 'PR-2026-002', 'due_at' => today()->addDays(4)->setTime(10, 0), 'status' => ActivityStatus::Pending, 'priority' => Priority::Medium],
            ['title' => 'Richiamare dopo colloquio conoscitivo', 'type' => ActivityType::FollowUp, 'contact' => 'davide.moretti@example.test', 'due_at' => today()->subDay()->setTime(15, 0), 'status' => ActivityStatus::Pending, 'priority' => Priority::High],
            ['title' => 'Confermare appuntamento revisione annuale', 'type' => ActivityType::Reminder, 'contact' => 'luigi.iommelli@example.test', 'appointment' => 'Revisione annuale del portafoglio', 'due_at' => today()->addDay()->setTime(8, 30), 'status' => ActivityStatus::Completed, 'priority' => Priority::Low, 'completed_at' => now()->subDay()],
        ];

        foreach ($activities as $data) {
            Activity::query()->updateOrCreate(['title' => $data['title']], ['type' => $data['type'], 'contact_id' => $contacts[$data['contact']]->id, 'company_id' => null, 'practice_id' => isset($data['practice']) ? $practices[$data['practice']]->id : null, 'appointment_id' => isset($data['appointment']) ? $appointments[$data['appointment']]->id : null, 'due_at' => $data['due_at'], 'scheduled_at' => $data['due_at']->copy()->subHour(), 'status' => $data['status'], 'priority' => $data['priority'], 'completed_at' => $data['completed_at'] ?? null, 'owner_id' => $owner->id]);
        }
    }
}
