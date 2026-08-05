<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_sanctum_token_and_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'tony@patrion.it', 'password' => 'secret-password']);

        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-password']);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id)->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_dashboard_is_protected_and_returns_frontend_sections(): void
    {
        $user = User::factory()->create();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonStructure(['data' => ['generated_at', 'stats', 'next_appointment', 'priority_activities', 'featured_practices', 'goals']]);
    }

    public function test_contacts_and_appointments_are_scoped_and_searchable(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $contact = Contact::factory()->client()->create(['first_name' => 'Luigi', 'last_name' => 'Iommelli']);
        Appointment::factory()->create(['owner_id' => $user->id, 'contact_id' => $contact->id, 'company_id' => null]);
        Appointment::factory()->create(['owner_id' => $other->id, 'contact_id' => $contact->id, 'company_id' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/contacts?search=Luigi')->assertOk()->assertJsonPath('data.0.first_name', 'Luigi');
        $this->getJson('/api/v1/appointments')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_upload_profile_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', ['name' => 'Tony Patrion', 'avatar' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Tony Patrion');

        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_contact_complete_profile_and_private_photo_are_available_via_api(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/contacts', [
            'first_name' => 'Mario', 'last_name' => 'Rossi', 'status' => 'client',
            'priority' => 'high', 'residence' => 'Roma', 'children_count' => 2,
            'interests' => ['investments'], 'photo' => UploadedFile::fake()->image('mario.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.contact.residence', 'Roma');
        $contactId = $response->json('data.contact.id');
        $contact = Contact::query()->findOrFail($contactId);
        Storage::disk('local')->assertExists($contact->photo_path);
        $this->get("/api/v1/contacts/{$contactId}/photo", ['Accept' => 'image/*'])->assertOk();

        $this->post("/api/v1/contacts/{$contactId}", [
            '_method' => 'PATCH', 'first_name' => 'Mario', 'last_name' => 'Rossi',
            'status' => 'client', 'priority' => 'high', 'personality_style' => 'Analitico',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.contact.personality_style', 'Analitico');
    }

    public function test_contact_extended_data_professionals_goals_and_emails_are_available(): void
    {
        $user = User::factory()->create(['email' => 'admin@patrion.it']);
        Sanctum::actingAs($user);

        $contact = Contact::factory()->create(['status' => 'client']);
        $this->patchJson("/api/v1/contacts/{$contact->id}", ['client_type' => 'business', 'tags' => ['Premium'], 'relationship_score' => 5, 'assigned_user_id' => $user->id])->assertOk()->assertJsonPath('data.contact.relationship_score', 5);
        $this->postJson("/api/v1/contacts/{$contact->id}/professionals", ['name' => 'Andrea Ferri', 'role' => 'Commercialista'])->assertCreated();
        $this->postJson("/api/v1/contacts/{$contact->id}/goals", ['title' => 'Riserva di liquidità', 'progress_percentage' => 25])->assertCreated();
        $this->postJson('/api/v1/emails', ['contact_id' => $contact->id, 'sender_name' => 'Mario Rossi', 'sender_email' => 'mario@example.test', 'recipient_email' => 'admin@patrion.it', 'subject' => 'Documenti', 'body' => 'Invio documenti', 'direction' => 'incoming', 'is_important' => true])->assertCreated();
        $this->getJson('/api/v1/emails?is_read=0')->assertOk()->assertJsonPath('data.0.subject', 'Documenti');
        $this->getJson("/api/v1/contacts/{$contact->id}")->assertOk()->assertJsonStructure(['data' => ['contact' => ['professionals', 'client_goals', 'assigned_user']]]);
    }
}
