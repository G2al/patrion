<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\ContactStatus;
use App\Enums\PracticeStatus;
use App\Enums\Priority;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Widgets\ActiveGoalsWidget;
use App\Filament\Widgets\DueActivitiesWidget;
use App\Filament\Widgets\ExpiringDocumentsWidget;
use App\Filament\Widgets\OperationalPracticesWidget;
use App\Filament\Widgets\TodayAppointmentsWidget;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Goal;
use App\Models\Note;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\TimelineEvent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StepThreeDashboardAndDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_the_patrion_admin_on_a_fresh_database(): void
    {
        Storage::fake('local');

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->sole();

        $this->assertSame('admin', $admin->name);
        $this->assertSame('admin@patrion.it', $admin->email);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_dashboard_requires_authentication_and_renders_for_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Centro operativo');
    }

    public function test_dashboard_widgets_show_operational_data_and_derived_states(): void
    {
        $this->travelTo(today()->setTime(12, 0));
        $user = User::factory()->create();
        $contact = Contact::factory()->client()->create();
        $type = PracticeType::factory()->create();
        $appointment = Appointment::factory()->scheduled()->create(['contact_id' => $contact->id, 'owner_id' => $user->id, 'starts_at' => today()->setTime(8, 0), 'ends_at' => today()->setTime(9, 0)]);
        Activity::factory()->overdue()->create(['title' => 'Attività urgente widget', 'contact_id' => $contact->id, 'owner_id' => $user->id]);
        Practice::factory()->inProgress()->create(['title' => 'Pratica ferma widget', 'contact_id' => $contact->id, 'practice_type_id' => $type->id, 'owner_id' => $user->id, 'updated_at' => now()->subDays(10)]);
        Practice::factory()->completed()->create(['contact_id' => $contact->id, 'practice_type_id' => $type->id, 'owner_id' => $user->id, 'completed_at' => today()]);
        Goal::factory()->active()->create(['title' => 'Obiettivo widget', 'practice_type_id' => $type->id, 'target_quantity' => 2, 'owner_id' => $user->id]);
        Document::factory()->expiringSoon()->create(['name' => 'Documento widget', 'contact_id' => $contact->id, 'uploaded_by_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(TodayAppointmentsWidget::class)->assertSee($appointment->title)->assertSee('Da consuntivare');
        Livewire::test(DueActivitiesWidget::class)->assertSee('Attività urgente widget');
        Livewire::test(OperationalPracticesWidget::class)->assertSee('Pratica ferma widget')->assertSee('Sì');
        Livewire::test(ActiveGoalsWidget::class)->assertSee('Obiettivo widget')->assertSee('1 / 2');
        Livewire::test(ExpiringDocumentsWidget::class)->assertSee('Documento widget');
    }

    public function test_factories_produce_coherent_states(): void
    {
        $user = User::factory()->create();
        $prospect = Contact::factory()->prospect()->highPriority()->withExpiredFollowUp()->create();
        $practice = Practice::factory()->completed()->create(['contact_id' => $prospect->id, 'owner_id' => $user->id]);
        $activity = Activity::factory()->followUp()->overdue()->create(['contact_id' => $prospect->id, 'owner_id' => $user->id]);

        $this->assertSame(ContactStatus::Prospect, $prospect->status);
        $this->assertTrue($prospect->next_follow_up_at->isPast());
        $this->assertSame(PracticeStatus::Completed, $practice->status);
        $this->assertNotNull($practice->completed_at);
        $this->assertSame(ActivityType::FollowUp, $activity->type);
        $this->assertSame(ActivityStatus::Pending, $activity->status);
    }

    public function test_global_search_configuration_covers_primary_attributes(): void
    {
        $this->assertContains('tax_code', ClientResource::getGloballySearchableAttributes());
        $this->assertContains('vat_number', CompanyResource::getGloballySearchableAttributes());
        $this->assertContains('contact.last_name', PracticeResource::getGloballySearchableAttributes());
        $this->assertContains('company.name', AppointmentResource::getGloballySearchableAttributes());
        $this->assertContains('practice.title', DocumentResource::getGloballySearchableAttributes());

        $user = User::factory()->create();
        Contact::factory()->client()->create(['first_name' => 'Giulia', 'last_name' => 'Ricercabile']);
        $this->actingAs($user);
        $results = ClientResource::getGlobalSearchResults('Ricercabile');

        $this->assertCount(1, $results);
        $this->assertSame('Giulia Ricercabile', (string) $results->first()->title);
    }

    public function test_primary_prospect_filter_applies_to_the_filament_table(): void
    {
        $user = User::factory()->create();
        $high = Contact::factory()->prospect()->highPriority()->create();
        $medium = Contact::factory()->prospect()->create(['priority' => Priority::Medium]);
        $this->actingAs($user);

        Livewire::test(ListProspects::class)
            ->filterTable('priority', Priority::High->value)
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$medium]);
    }

    public function test_private_document_download_requires_an_authenticated_admin(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $contact = Contact::factory()->client()->create();
        Storage::disk('local')->put('documents/download-demo.txt', 'contenuto demo');
        $document = Document::factory()->create([
            'name' => 'download-demo.txt', 'file_path' => 'documents/download-demo.txt',
            'contact_id' => $contact->id, 'uploaded_by_id' => $user->id,
        ]);

        $this->get(DocumentResource::getUrl())->assertRedirect('/admin/login');

        $this->actingAs($user);
        Livewire::test(ListDocuments::class)
            ->callTableAction('download', $document)
            ->assertFileDownloaded('download-demo.txt');
    }

    public function test_demo_seeder_is_rich_repeatable_and_preserves_the_only_user(): void
    {
        Storage::fake('local');
        User::factory()->create(['name' => 'Antonio Tulipano']);

        $this->seed(DatabaseSeeder::class);
        $counts = $this->demoCounts();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->count());
        $this->assertSame($counts, $this->demoCounts());
        $this->assertSame(3, Contact::query()->clients()->count());
        $this->assertSame(2, Contact::query()->prospects()->count());
        $this->assertSame(1, Practice::query()->completed()->whereHas('practiceType', fn ($query) => $query->where('slug', 'pac'))->whereBetween('completed_at', [today()->startOfMonth(), today()->endOfMonth()])->count());
        $this->assertGreaterThan(0, Appointment::query()->whereDate('starts_at', today())->count());
        $this->assertGreaterThan(0, Activity::query()->where('due_at', '<', now())->where('status', '!=', ActivityStatus::Completed)->count());
        $this->assertGreaterThan(0, Document::query()->whereBetween('expires_at', [today(), today()->addDays(30)])->count());
        Storage::disk('local')->assertExists('seed-documents/documento-identità-luigi-iommelli.txt');
    }

    /** @return array<string, int> */
    private function demoCounts(): array
    {
        return [
            'contacts' => Contact::query()->count(), 'companies' => Company::query()->count(),
            'appointments' => Appointment::query()->count(), 'activities' => Activity::query()->count(),
            'practices' => Practice::query()->count(), 'goals' => Goal::query()->count(),
            'documents' => Document::query()->count(), 'notes' => Note::query()->count(),
            'timeline' => TimelineEvent::query()->count(),
        ];
    }
}
