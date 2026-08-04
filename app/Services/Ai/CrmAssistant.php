<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiToolCall;
use App\Models\User;
use JsonException;
use Throwable;

class CrmAssistant
{
    public function __construct(
        private readonly OpenAiResponsesClient $client,
        private readonly CrmToolRegistry $tools,
        private readonly SystemPrompt $prompt,
        private readonly CrmIntentRouter $router,
    ) {}

    /** @return array{text: string, metadata: array<string, mixed>, tool_call_ids: array<int, int>} */
    public function answer(AiConversation $conversation, User $user, ?callable $onTextDelta = null, ?callable $onStatus = null): array
    {
        if ($conversation->user_id !== $user->id) {
            throw new AiAssistantException('Non puoi accedere a questa conversazione.');
        }

        $currentQuestion = (string) $conversation->messages()->reorder()->where('role', 'user')->latest('id')->value('content');
        $route = $this->router->route($currentQuestion);
        $input = $conversation->messages()
            ->reorder()
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->reject(fn ($message): bool => (bool) data_get($message->metadata, 'error', false))
            ->map(fn ($message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();

        $toolCallIds = [];
        $maxRounds = max(1, (int) config('services.openai.max_tool_rounds', 6));

        for ($round = 1; $round <= $maxRounds; $round++) {
            $payload = $this->payload($input, $user, $currentQuestion, $route);
            $response = $onTextDelta === null
                ? $this->client->create($payload)
                : $this->client->createStreamed($payload, $onTextDelta);
            $output = is_array($response['output'] ?? null) ? $response['output'] : [];
            $calls = array_values(array_filter($output, fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'function_call'));

            if ($calls === []) {
                $text = $this->extractText($response);

                if (blank($text)) {
                    throw new AiAssistantException('OpenAI non ha prodotto una risposta testuale.');
                }

                return [
                    'text' => $text,
                    'metadata' => [
                        'response_id' => $response['id'] ?? null,
                        'model' => $response['model'] ?? config('services.openai.model'),
                        'usage' => $response['usage'] ?? null,
                        'tool_rounds' => $round - 1,
                    ],
                    'tool_call_ids' => $toolCallIds,
                ];
            }

            $toolOutputs = [];

            foreach ($calls as $call) {
                if ($onStatus !== null) {
                    $onStatus($this->statusForTool((string) ($call['name'] ?? '')));
                }

                $executed = $this->executeTool($conversation, $user, $call);
                $toolCallIds[] = $executed['audit_id'];
                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $call['call_id'],
                    'output' => json_encode($executed['output'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }

            $input = [...$input, ...$output, ...$toolOutputs];
        }

        throw new AiAssistantException('La richiesta richiede troppi passaggi. Prova a renderla più specifica.');
    }

    /** @param array<int, mixed> $input
     * @return array<string, mixed>
     */
    private function payload(array $input, User $user, string $currentQuestion, array $route): array
    {
        $payload = [
            'model' => (string) config('services.openai.model', 'gpt-5.4-nano'),
            'instructions' => $this->prompt->for($user, $currentQuestion, $route['guidance']),
            'input' => $input,
            'reasoning' => ['effort' => (string) config('services.openai.reasoning_effort', 'low')],
            'text' => ['verbosity' => (string) config('services.openai.verbosity', 'medium')],
            'max_output_tokens' => (int) config('services.openai.max_output_tokens', 3000),
            'store' => false,
        ];

        if ($route['tools'] !== []) {
            $payload['tools'] = $this->tools->definitions($route['tools']);
            $payload['tool_choice'] = 'auto';
            $payload['parallel_tool_calls'] = false;
        }

        return $payload;
    }

    private function statusForTool(string $tool): string
    {
        return match ($tool) {
            'get_appointments' => 'Controllo l’agenda…',
            'search_contacts', 'get_contact_history' => 'Consulto clienti e prospect…',
            'search_companies', 'get_company_history' => 'Consulto le aziende…',
            'get_goal_progress' => 'Calcolo il progresso degli obiettivi…',
            'get_due_activities' => 'Controllo attività e scadenze…',
            'get_practices' => 'Analizzo le pratiche…',
            'get_expiring_documents' => 'Controllo i documenti…',
            'get_client_rankings' => 'Calcolo la classifica clienti…',
            default => 'Consulto il gestionale…',
        };
    }

    /** @param array<string, mixed> $call
     * @return array{audit_id: int, output: array<string, mixed>}
     */
    private function executeTool(AiConversation $conversation, User $user, array $call): array
    {
        $startedAt = hrtime(true);
        $name = (string) ($call['name'] ?? 'unknown');
        $arguments = $this->decodeArguments($call['arguments'] ?? '{}');
        $audit = AiToolCall::query()->create([
            'ai_conversation_id' => $conversation->id,
            'call_id' => $call['call_id'] ?? null,
            'name' => $name,
            'arguments' => $arguments,
            'status' => 'running',
        ]);

        try {
            $result = $this->tools->execute($name, $arguments, $user);
            $audit->update([
                'result' => $result,
                'status' => 'completed',
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            return ['audit_id' => $audit->id, 'output' => ['ok' => true, 'data' => $result]];
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Non è stato possibile recuperare i dati richiesti con questo strumento.';
            $audit->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            return ['audit_id' => $audit->id, 'output' => ['ok' => false, 'error' => $message]];
        }
    }

    /** @return array<string, mixed> */
    private function decodeArguments(mixed $arguments): array
    {
        if (is_array($arguments)) {
            return $arguments;
        }

        try {
            $decoded = json_decode((string) $arguments, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiAssistantException('OpenAI ha prodotto argomenti non validi per uno strumento.', previous: $exception);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $response */
    private function extractText(array $response): string
    {
        if (filled($response['output_text'] ?? null)) {
            return trim((string) $response['output_text']);
        }

        $parts = [];

        foreach ($response['output'] ?? [] as $item) {
            if (! is_array($item) || ($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (is_array($content) && ($content['type'] ?? null) === 'output_text' && filled($content['text'] ?? null)) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n\n", $parts));
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
