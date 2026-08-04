<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\User;
use App\Services\Ai\CrmAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

final class AiApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_ai_routes_are_protected_and_conversations_are_scoped(): void
    {
        $this->getJson('/api/v1/ai/conversations')->assertUnauthorized();
        $user = User::factory()->create();
        $other = User::factory()->create();
        AiConversation::query()->create(['user_id' => $other->id, 'title' => 'Privata']);
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/v1/ai/conversations')->assertCreated();
        $id = $created->json('data.conversation.id');

        $this->getJson('/api/v1/ai/conversations')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/ai/conversations/{$id}")->assertOk();
        $this->getJson('/api/v1/ai/conversations/1')->assertNotFound();
    }

    public function test_message_uses_existing_crm_assistant_and_returns_complete_answer(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Nuova conversazione']);
        Sanctum::actingAs($user);
        $this->mock(CrmAssistant::class, function (MockInterface $mock): void {
            $mock->shouldReceive('answer')->once()->andReturn([
                'text' => 'Oggi hai due appuntamenti.',
                'metadata' => ['model' => 'test', 'tool_rounds' => 1],
                'tool_call_ids' => [],
            ]);
        });

        $this->postJson("/api/v1/ai/conversations/{$conversation->id}/messages", ['content' => 'Con chi ho appuntamento oggi?'])
            ->assertOk()
            ->assertJsonPath('data.assistant_message.content', 'Oggi hai due appuntamenti.')
            ->assertJsonPath('data.user_message.role', 'user');

        $this->assertDatabaseHas('ai_conversations', ['id' => $conversation->id, 'title' => 'Con chi ho appuntamento oggi?']);
        $this->assertDatabaseCount('ai_messages', 2);
    }

    public function test_conversation_can_be_deleted_only_by_owner(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Da eliminare']);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/ai/conversations/{$conversation->id}")->assertOk();
        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
    }
}
