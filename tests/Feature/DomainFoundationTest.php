<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\GoalStatus;
use App\Enums\PracticeStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\Note;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\PracticeTypeSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_and_essential_columns_exist(): void
    {
        foreach ([
            'contacts',
            'companies',
            'company_contact',
            'practice_types',
            'practices',
            'appointments',
            'activities',
            'goals',
            'documents',
            'notes',
            'timeline_events',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('contacts', ['status', 'interests', 'personal_goals', 'next_follow_up_at']));
        $this->assertTrue(Schema::hasColumns('practices', ['practice_type_id', 'contact_id', 'company_id', 'completed_at']));
        $this->assertTrue(Schema::hasColumns('timeline_events', ['subject_type', 'subject_id', 'metadata', 'occurred_at']));
    }

    public function test_practice_type_seeder_is_separate_complete_and_idempotent(): void
    {
        $this->seed(PracticeTypeSeeder::class);
        $this->seed(PracticeTypeSeeder::class);

        $this->assertDatabaseCount('practice_types', 6);
        $this->assertDatabaseHas('practice_types', ['name' => 'PAC', 'slug' => 'pac', 'is_active' => true]);
        $this->assertDatabaseHas('practice_types', ['name' => 'Investimento classico']);
    }

    public function test_contact_company_notes_and_timeline_relationships_work(): void
    {
        $user = User::factory()->create();
        $contact = Contact::query()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'status' => ContactStatus::Prospect,
            'interests' => ['investments'],
        ]);
        $company = Company::query()->create(['name' => 'Rossi S.r.l.']);

        $contact->companies()->attach($company, ['role' => 'administrator']);
        $note = $contact->notes()->create([
            'content' => 'Preferisce essere contattato al mattino.',
            'author_id' => $user->id,
        ]);
        $event = $contact->timelineEvents()->create([
            'event_type' => 'contact_created',
            'title' => 'Contatto creato',
            'metadata' => ['source' => 'referral'],
            'occurred_at' => now(),
            'author_id' => $user->id,
        ]);

        $this->assertTrue($contact->fresh()->companies->first()->is($company));
        $this->assertSame('administrator', $company->fresh()->contacts->first()->pivot->role);
        $this->assertTrue($note->noteable->is($contact));
        $this->assertTrue($event->subject->is($contact));
        $this->assertSame(['investments'], $contact->fresh()->interests);
        $this->assertSame(['source' => 'referral'], $event->fresh()->metadata);
    }

    public function test_practice_requires_one_principal_and_sets_completion_date(): void
    {
        $user = User::factory()->create();
        $contact = Contact::query()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $company = Company::query()->create(['name' => 'Verdi S.p.A.']);
        $type = PracticeType::query()->create(['name' => 'PAC', 'slug' => 'pac']);

        $practice = Practice::query()->create([
            'internal_number' => 'P-001',
            'title' => 'PAC cliente',
            'practice_type_id' => $type->id,
            'contact_id' => $contact->id,
            'status' => PracticeStatus::Completed,
            'opened_at' => today()->subDay(),
            'owner_id' => $user->id,
        ]);

        $this->assertNotNull($practice->completed_at);

        $this->expectException(DomainException::class);

        Practice::query()->create([
            'internal_number' => 'P-002',
            'title' => 'Soggetto ambiguo',
            'practice_type_id' => $type->id,
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'opened_at' => today(),
            'owner_id' => $user->id,
        ]);
    }

    public function test_note_requires_a_domain_owner(): void
    {
        $this->expectException(DomainException::class);

        Note::query()->create([
            'content' => 'Nota senza soggetto',
            'author_id' => User::factory()->create()->id,
        ]);
    }

    public function test_appointment_requires_one_principal_and_a_valid_time_range(): void
    {
        $user = User::factory()->create();
        $contact = Contact::query()->create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);

        $this->expectException(DomainException::class);

        Appointment::query()->create([
            'title' => 'Intervallo non valido',
            'contact_id' => $contact->id,
            'starts_at' => now(),
            'ends_at' => now()->subHour(),
            'status' => AppointmentStatus::Scheduled,
            'owner_id' => $user->id,
        ]);
    }

    public function test_goal_progress_is_calculated_from_completed_practices_in_period(): void
    {
        $user = User::factory()->create();
        $contact = Contact::query()->create(['first_name' => 'Sara', 'last_name' => 'Neri']);
        $type = PracticeType::query()->create(['name' => 'Prestito', 'slug' => 'prestito']);
        $otherType = PracticeType::query()->create(['name' => 'PAC', 'slug' => 'pac']);

        $this->createPractice($user, $contact, $type, 'P-101', PracticeStatus::Completed, today());
        $this->createPractice($user, $contact, $type, 'P-102', PracticeStatus::InProgress, null);
        $this->createPractice($user, $contact, $type, 'P-103', PracticeStatus::Completed, today()->subMonths(2));
        $this->createPractice($user, $contact, $otherType, 'P-104', PracticeStatus::Completed, today());

        $goal = Goal::query()->create([
            'title' => 'Tre prestiti nel mese',
            'practice_type_id' => $type->id,
            'target_quantity' => 3,
            'starts_at' => today()->startOfMonth(),
            'ends_at' => today()->endOfMonth(),
            'status' => GoalStatus::Active,
            'owner_id' => $user->id,
        ]);

        $this->assertSame(1, $goal->current_quantity);
        $this->assertSame(33.33, $goal->progress_percentage);
        $this->assertArrayNotHasKey('current_quantity', $goal->getAttributes());
    }

    public function test_filament_admin_routes_and_authentication_remain_available(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk();
    }

    private function createPractice(
        User $user,
        Contact $contact,
        PracticeType $type,
        string $number,
        PracticeStatus $status,
        mixed $completedAt,
    ): Practice {
        return Practice::query()->create([
            'internal_number' => $number,
            'title' => $number,
            'practice_type_id' => $type->id,
            'contact_id' => $contact->id,
            'status' => $status,
            'opened_at' => today()->subMonths(3),
            'completed_at' => $completedAt,
            'owner_id' => $user->id,
        ]);
    }
}
