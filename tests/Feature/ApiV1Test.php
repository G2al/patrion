<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
