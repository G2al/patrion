<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AiChat;
use App\Models\AiConversation;
use App\Models\AiToolCall;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use App\Services\Ai\CrmAssistant;
use App\Services\Ai\CrmIntentRouter;
use App\Services\Ai\CrmToolRegistry;
use App\Services\Ai\OpenAiResponsesClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_is_global_in_the_authenticated_panel_and_has_no_separate_page(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('Apri Assistente AI');
        $this->get('/admin/ai-assistant')->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Apri Assistente AI');
    }

    public function test_crm_tools_return_owned_appointments_and_goal_progress(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $contact = Contact::factory()->client()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);
        Appointment::factory()->create(['owner_id' => $user->id, 'contact_id' => $contact->id, 'company_id' => null, 'starts_at' => today()->setTime(10, 0), 'ends_at' => today()->setTime(11, 0)]);
        Appointment::factory()->create(['owner_id' => $otherUser->id, 'starts_at' => today()->setTime(12, 0), 'ends_at' => today()->setTime(13, 0)]);
        $type = PracticeType::factory()->create();
        Goal::factory()->create(['owner_id' => $user->id, 'practice_type_id' => $type->id, 'target_quantity' => 5]);

        $tools = app(CrmToolRegistry::class);
        $appointments = $tools->execute('get_appointments', ['from' => today()->toDateString(), 'to' => today()->toDateString(), 'query' => null, 'limit' => 10], $user);
        $goals = $tools->execute('get_goal_progress', ['status' => 'active'], $user);

        $this->assertSame(1, $appointments['count']);
        $this->assertSame('Mario Rossi', $appointments['items'][0]['subject']);
        $this->assertStringContainsString('/admin/appointments/', $appointments['items'][0]['url']);
        $this->assertSame(1, $goals['count']);
        $this->assertSame(5, $goals['items'][0]['remaining_quantity']);
        $this->assertSame('pratiche completate', $goals['items'][0]['metric']);
    }

    public function test_tools_without_parameters_are_serialized_as_json_objects(): void
    {
        $definitions = app(CrmToolRegistry::class)->definitions();
        $overview = collect($definitions)->firstWhere('name', 'get_crm_overview');

        $this->assertIsObject($overview['parameters']['properties']);
        $this->assertStringContainsString(
            '"name":"get_crm_overview","description"',
            json_encode($definitions, JSON_THROW_ON_ERROR),
        );
        $this->assertStringContainsString('"properties":{}', json_encode($overview, JSON_THROW_ON_ERROR));
    }

    public function test_client_ranking_uses_completed_practice_value(): void
    {
        $user = User::factory()->create();
        $best = Contact::factory()->client()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);
        $second = Contact::factory()->client()->create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);
        Practice::factory()->completed()->create(['owner_id' => $user->id, 'contact_id' => $best->id, 'actual_value' => 50000]);
        Practice::factory()->completed()->create(['owner_id' => $user->id, 'contact_id' => $second->id, 'actual_value' => 15000]);

        $ranking = app(CrmToolRegistry::class)->execute('get_client_rankings', [
            'metric' => 'commercial_value',
            'limit' => 5,
        ], $user);

        $this->assertSame('valore effettivo delle pratiche completate', $ranking['metric_label']);
        $this->assertSame('Anna Verdi', $ranking['items'][0]['name']);
        $this->assertSame(50000.0, $ranking['items'][0]['completed_practices_value']);
    }

    public function test_client_ranking_does_not_invent_a_winner_when_every_value_is_zero(): void
    {
        $user = User::factory()->create();
        $client = Contact::factory()->client()->create();
        Practice::factory()->completed()->create([
            'owner_id' => $user->id,
            'contact_id' => $client->id,
            'actual_value' => null,
        ]);

        $ranking = app(CrmToolRegistry::class)->execute('get_client_rankings', [
            'metric' => 'commercial_value',
            'limit' => 5,
        ], $user);

        $this->assertFalse($ranking['ranking_available']);
        $this->assertSame(0, $ranking['count']);
        $this->assertSame([], $ranking['items']);
        $this->assertStringContainsString('Non indicare un miglior cliente', $ranking['unavailable_reason']);
    }

    public function test_prospect_outcomes_count_unique_prospects_instead_of_practices_or_companies(): void
    {
        $user = User::factory()->create();
        $lostByPractice = Contact::factory()->prospect()->create();
        $lostByAppointment = Contact::factory()->prospect()->create();
        $activeProspect = Contact::factory()->prospect()->create();
        $client = Contact::factory()->client()->create();

        Practice::factory()->unsuccessful()->count(2)->create(['owner_id' => $user->id, 'contact_id' => $lostByPractice->id]);
        Practice::factory()->unsuccessful()->create(['owner_id' => $user->id, 'contact_id' => $client->id]);
        Appointment::factory()->completed()->create([
            'owner_id' => $user->id,
            'contact_id' => $lostByAppointment->id,
            'company_id' => null,
            'outcome' => 'negative',
        ]);

        $outcomes = app(CrmToolRegistry::class)->execute('get_prospect_outcomes', [], $user);

        $this->assertSame(3, $outcomes['current_prospects_total']);
        $this->assertSame(2, $outcomes['not_acquired_prospects_count']);
        $this->assertEqualsCanonicalizing(
            [$lostByPractice->id, $lostByAppointment->id],
            collect($outcomes['items'])->pluck('id')->all(),
        );
        $this->assertNotContains($activeProspect->id, collect($outcomes['items'])->pluck('id')->all());
    }

    public function test_router_understands_misspelled_lost_prospect_question(): void
    {
        $route = app(CrmIntentRouter::class)->route('Quanti prospct non sono riuscito a concludere?');

        $this->assertSame(['get_prospect_outcomes'], $route['tools']);
    }

    public function test_router_keeps_multiple_tools_for_operational_prospect_analysis(): void
    {
        $route = app(CrmIntentRouter::class)->route('Quali prospect non acquisiti devo prioritizzare e quali attività urgenti ho?');

        $this->assertContains('get_prospect_outcomes', $route['tools']);
        $this->assertContains('get_due_activities', $route['tools']);
    }

    public function test_contact_history_translates_internal_codes_for_the_assistant(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->prospect()->create([
            'interests' => ['other', 'pension'],
            'personal_goals' => ['retirement', 'savings', 'income'],
        ]);

        $history = app(CrmToolRegistry::class)->execute('get_contact_history', ['contact_id' => $contact->id], $user);

        $this->assertSame(['Altro', 'Previdenza'], $history['contact']['interests']);
        $this->assertSame(['Pensione', 'Accumulo', 'Reddito'], $history['contact']['personal_goals']);
    }

    public function test_latest_question_is_last_and_conversational_turn_has_no_crm_tools(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        $user = User::factory()->create();
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Cambio argomento']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Con chi ho appuntamento oggi?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Hai due appuntamenti.']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Come stai?']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'resp_small_talk',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [['type' => 'output_text', 'text' => 'Sto bene, grazie! Come posso aiutarti?']],
                ]],
            ]),
        ]);

        $answer = app(CrmAssistant::class)->answer($conversation, $user);

        $this->assertSame('Sto bene, grazie! Come posso aiutarti?', $answer['text']);
        Http::assertSent(function (Request $request): bool {
            $input = $request['input'];

            return ! isset($request['tools'])
                && end($input)['content'] === 'Come stai?'
                && str_contains($request['instructions'], '<richiesta_corrente>')
                && str_contains($request['instructions'], 'Come stai?');
        });
    }

    public function test_best_client_question_exposes_only_the_ranking_tool(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        $user = User::factory()->create();
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Miglior cliente']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Chi è il mio miglior cliente?']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'resp_ranking',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [['type' => 'output_text', 'text' => 'Calcolo il miglior cliente sul valore delle pratiche concluse.']],
                ]],
            ]),
        ]);

        app(CrmAssistant::class)->answer($conversation, $user);

        Http::assertSent(fn (Request $request): bool => collect($request['tools'] ?? [])->pluck('name')->all() === ['get_client_rankings']);
    }

    public function test_responses_api_stream_is_parsed_incrementally(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        $completed = [
            'id' => 'resp_stream',
            'model' => 'gpt-5.4-nano',
            'output' => [[
                'type' => 'message',
                'role' => 'assistant',
                'content' => [['type' => 'output_text', 'text' => 'Ciao Patrion']],
            ]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 2],
        ];
        $sse = 'data: '.json_encode(['type' => 'response.output_text.delta', 'delta' => 'Ciao '])."\n\n"
            .'data: '.json_encode(['type' => 'response.output_text.delta', 'delta' => 'Patrion'])."\n\n"
            .'data: '.json_encode(['type' => 'response.completed', 'response' => $completed])."\n\n";

        Http::fake([
            'api.openai.com/*' => Http::response($sse, 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $deltas = [];
        $response = app(OpenAiResponsesClient::class)->createStreamed(
            ['model' => 'gpt-5.4-nano', 'input' => 'Ciao'],
            function (string $delta) use (&$deltas): void {
                $deltas[] = $delta;
            },
        );

        $this->assertSame(['Ciao ', 'Patrion'], $deltas);
        $this->assertSame('resp_stream', $response['id']);
        Http::assertSent(fn (Request $request): bool => $request['stream'] === true);
    }

    public function test_assistant_executes_tool_call_and_returns_grounded_answer(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.model', 'gpt-5.4-nano');
        $user = User::factory()->create();
        $contact = Contact::factory()->client()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);
        Appointment::factory()->create(['owner_id' => $user->id, 'contact_id' => $contact->id, 'company_id' => null, 'starts_at' => today()->setTime(10, 0), 'ends_at' => today()->setTime(11, 0)]);
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Appuntamenti di oggi']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Con chi ho appuntamento oggi?']);

        Http::fakeSequence()
            ->push([
                'id' => 'resp_tool',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'function_call',
                    'call_id' => 'call_appointments',
                    'name' => 'get_appointments',
                    'arguments' => json_encode(['from' => today()->toDateString(), 'to' => today()->toDateString(), 'query' => null, 'limit' => 10]),
                ]],
            ])
            ->push([
                'id' => 'resp_final',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [['type' => 'output_text', 'text' => 'Oggi hai un appuntamento alle 10:00 con Mario Rossi.']],
                ]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
            ]);

        $answer = app(CrmAssistant::class)->answer($conversation, $user);

        $this->assertSame('Oggi hai un appuntamento alle 10:00 con Mario Rossi.', $answer['text']);
        $this->assertSame('gpt-5.4-nano', $answer['metadata']['model']);
        $this->assertCount(1, $answer['tool_call_ids']);
        $this->assertDatabaseHas('ai_tool_calls', ['name' => 'get_appointments', 'status' => 'completed']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request['model'] === 'gpt-5.4-nano'
            && $request['store'] === false
            && $request['reasoning']['effort'] === 'low'
            && str_contains($request['instructions'], 'Non inventare mai')
            && str_contains($request['instructions'], '<verification_loop>')
            && str_contains($request['instructions'], 'Non terminare con domande generiche'));
        Http::assertSent(fn (Request $request): bool => collect($request['input'])->contains(
            fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'function_call_output' && str_contains($item['output'], 'Mario Rossi')
        ));
    }

    public function test_livewire_chat_persists_conversation_answer_and_tool_audit_link(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        $user = User::factory()->create();
        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'resp_final',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [['type' => 'output_text', 'text' => 'Ecco la panoramica richiesta.']],
                ]],
            ]),
        ]);

        $this->actingAs($user);
        Livewire::test(AiChat::class)
            ->call('openChat')
            ->assertSet('isOpen', true)
            ->assertSee('Assistente Patrion')
            ->set('message', 'Dammi una panoramica del gestionale')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Ecco la panoramica richiesta.');

        $conversation = AiConversation::query()->sole();
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertSame(['user', 'assistant'], $conversation->messages()->pluck('role')->all());
        $this->assertSame('resp_final', $conversation->messages()->where('role', 'assistant')->sole()->metadata['response_id']);
        $this->assertSame(0, AiToolCall::query()->count());
    }

    public function test_quick_question_is_sent_immediately(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        $user = User::factory()->create();
        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'resp_quick',
                'model' => 'gpt-5.4-nano',
                'output' => [[
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [['type' => 'output_text', 'text' => 'Non risultano attività scadute.']],
                ]],
            ]),
        ]);

        $this->actingAs($user);
        Livewire::test(AiChat::class)
            ->call('openChat')
            ->call('askSuggestion', 'Quali attività sono scadute?')
            ->assertSet('message', '')
            ->assertSee('Non risultano attività scadute.');

        $this->assertDatabaseHas('ai_messages', ['role' => 'user', 'content' => 'Quali attività sono scadute?']);
    }

    public function test_assistant_markdown_strips_embedded_html(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::query()->create(['user_id' => $user->id, 'title' => 'Sicurezza']);
        $message = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => "<script>alert(1)</script>\n\n**Risposta sicura**",
        ]);

        $html = $message->renderedContent()->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>Risposta sicura</strong>', $html);
    }
}
