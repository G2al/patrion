<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\ConvertProspectToClient;
use App\Actions\ReportAppointment;
use App\Data\ReportAppointmentData;
use App\Enums\ActivityType;
use App\Enums\AppointmentOutcome;
use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\PracticeStatus;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Goals\GoalResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\PracticeTypes\PracticeTypeResource;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StepTwoWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_and_prospect_resources_filter_the_same_contact_model(): void
    {
        $client = $this->contact(ContactStatus::Client, 'Cliente');
        $prospect = $this->contact(ContactStatus::Prospect, 'Prospect');

        $this->assertTrue(ClientResource::getEloquentQuery()->sole()->is($client));
        $this->assertTrue(ProspectResource::getEloquentQuery()->sole()->is($prospect));
        $this->assertDatabaseCount('contacts', 2);
    }

    public function test_conversion_keeps_the_record_and_all_its_relationships(): void
    {
        $user = User::factory()->create();
        $prospect = $this->contact(ContactStatus::Prospect);
        $company = Company::query()->create(['name' => 'Tulipano Consulting']);
        $prospect->companies()->attach($company, ['role' => 'contact_person']);
        $appointment = $this->appointment($user, $prospect);

        $converted = app(ConvertProspectToClient::class)->handle($prospect, $user->id);

        $this->assertSame($prospect->id, $converted->id);
        $this->assertSame(ContactStatus::Client, $converted->status);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertTrue($converted->companies->first()->is($company));
        $this->assertTrue($converted->appointments->first()->is($appointment));
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $prospect->id, 'event_type' => 'prospect_converted']);
    }

    public function test_practice_completion_sets_date_and_records_timeline(): void
    {
        $user = User::factory()->create();
        $contact = $this->contact();
        $type = PracticeType::query()->create(['name' => 'PAC', 'slug' => 'pac']);
        $practice = $this->practice($user, $contact, $type);

        $practice->update(['status' => PracticeStatus::Completed]);

        $this->assertNotNull($practice->fresh()->completed_at);
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $contact->id, 'event_type' => 'practice_status_changed']);
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $contact->id, 'event_type' => 'practice_completed']);

        $practice->update(['status' => PracticeStatus::InProgress]);
        $this->assertNull($practice->fresh()->completed_at);
    }

    public function test_reporting_an_appointment_runs_the_requested_workflows_atomically(): void
    {
        $user = User::factory()->create();
        $prospect = $this->contact(ContactStatus::Prospect);
        $type = PracticeType::query()->create(['name' => 'Prestito', 'slug' => 'prestito']);
        $appointment = $this->appointment($user, $prospect);
        $nextContact = CarbonImmutable::now()->addWeek();

        $result = app(ReportAppointment::class)->handle($appointment, new ReportAppointmentData(
            occurred: true,
            outcome: AppointmentOutcome::Positive,
            emergedNeed: 'Finanziamento prima casa',
            prospectInterested: true,
            convertToClient: true,
            openPractice: true,
            practiceTypeId: $type->id,
            followUpRequired: true,
            nextContactAt: $nextContact,
            notes: 'Inviare il riepilogo.',
        ), $user->id);

        $this->assertSame(AppointmentStatus::Completed, $result['appointment']->status);
        $this->assertSame(ContactStatus::Client, $prospect->fresh()->status);
        $this->assertSame($type->id, $result['practice']?->practice_type_id);
        $this->assertSame(ActivityType::FollowUp, $result['follow_up']?->type);
        $this->assertTrue($result['follow_up']?->appointment->is($appointment));
        $this->assertEquals($nextContact->format('Y-m-d H:i'), $prospect->fresh()->next_follow_up_at?->format('Y-m-d H:i'));
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $prospect->id, 'event_type' => 'appointment_reported']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_activity_completion_and_documents_create_timeline_events_on_private_storage(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $contact = $this->contact();
        $activity = $contact->activities()->create([
            'title' => 'Richiamare cliente',
            'type' => ActivityType::PhoneCall,
            'owner_id' => $user->id,
        ]);

        $activity->update(['status' => 'completed']);
        Storage::disk('local')->put('documents/prova.pdf', 'documento privato');
        Document::query()->create([
            'name' => 'prova.pdf',
            'file_path' => 'documents/prova.pdf',
            'disk' => 'local',
            'contact_id' => $contact->id,
            'uploaded_by_id' => $user->id,
        ]);

        Storage::disk('local')->assertExists('documents/prova.pdf');
        $this->assertStringContainsString('private', (string) config('filesystems.disks.local.root'));
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $contact->id, 'event_type' => 'activity_completed']);
        $this->assertDatabaseHas('timeline_events', ['subject_id' => $contact->id, 'event_type' => 'document_uploaded']);
    }

    public function test_admin_user_can_render_all_filament_resource_lists_and_detail_pages(): void
    {
        $user = User::factory()->create();
        $contact = $this->contact(ContactStatus::Client);
        $company = Company::query()->create(['name' => 'Azienda Demo']);

        $this->actingAs($user);

        foreach ([
            ClientResource::getUrl(), ProspectResource::getUrl(), CompanyResource::getUrl(),
            AppointmentResource::getUrl(), ActivityResource::getUrl(), PracticeResource::getUrl(),
            GoalResource::getUrl(), DocumentResource::getUrl(), PracticeTypeResource::getUrl(),
            ClientResource::getUrl('view', ['record' => $contact]),
            CompanyResource::getUrl('view', ['record' => $company]),
            CompanyResource::getUrl('create'),
            CompanyResource::getUrl('edit', ['record' => $company]),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    private function contact(ContactStatus $status = ContactStatus::Prospect, string $lastName = 'Rossi'): Contact
    {
        return Contact::query()->create(['first_name' => 'Mario', 'last_name' => $lastName, 'status' => $status]);
    }

    private function appointment(User $user, Contact $contact): Appointment
    {
        return Appointment::query()->create([
            'title' => 'Analisi esigenze', 'contact_id' => $contact->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'owner_id' => $user->id,
        ]);
    }

    private function practice(User $user, Contact $contact, PracticeType $type): Practice
    {
        return Practice::query()->create([
            'internal_number' => 'PR-TEST-001', 'title' => 'Pratica test',
            'practice_type_id' => $type->id, 'contact_id' => $contact->id,
            'opened_at' => today(), 'owner_id' => $user->id,
        ]);
    }
}
