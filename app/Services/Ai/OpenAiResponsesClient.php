<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiResponsesClient
{
    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        $apiKey = (string) config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new AiAssistantException('Configura OPENAI_API_KEY nel file .env prima di usare l’assistente.');
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.openai.timeout', 60))
                ->retry(2, 300, throw: false)
                ->post('/responses', $payload);
        } catch (ConnectionException $exception) {
            throw new AiAssistantException('OpenAI non è raggiungibile in questo momento. Riprova tra poco.', previous: $exception);
        }

        if ($response->failed()) {
            $this->reportFailure($response);

            throw new AiAssistantException($this->publicErrorMessage($response));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new AiAssistantException('OpenAI ha restituito una risposta non valida.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(string): void  $onTextDelta
     * @return array<string, mixed>
     */
    public function createStreamed(array $payload, callable $onTextDelta): array
    {
        $apiKey = (string) config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new AiAssistantException('Configura OPENAI_API_KEY nel file .env prima di usare l’assistente.');
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.openai.timeout', 60))
                ->withOptions(['stream' => true])
                ->post('/responses', [...$payload, 'stream' => true]);
        } catch (ConnectionException $exception) {
            throw new AiAssistantException('OpenAI non è raggiungibile in questo momento. Riprova tra poco.', previous: $exception);
        }

        if ($response->failed()) {
            $this->reportFailure($response);

            throw new AiAssistantException($this->publicErrorMessage($response));
        }

        $contentType = strtolower((string) $response->header('content-type'));

        // Laravel HTTP fakes and some compatible gateways return regular JSON.
        if (! str_contains($contentType, 'text/event-stream')) {
            $data = $response->json();

            if (! is_array($data)) {
                throw new AiAssistantException('OpenAI ha restituito una risposta non valida.');
            }

            return $data;
        }

        $stream = $response->toPsrResponse()->getBody();
        $buffer = '';
        $completed = null;

        while (! $stream->eof()) {
            $buffer .= $stream->read(8192);
            $this->consumeEvents($buffer, $completed, $onTextDelta);
        }

        $buffer .= "\n\n";
        $this->consumeEvents($buffer, $completed, $onTextDelta);

        if (! is_array($completed)) {
            throw new AiAssistantException('La risposta in streaming di OpenAI è terminata in modo inatteso.');
        }

        return $completed;
    }

    /** @param array<string, mixed>|null $completed */
    private function consumeEvents(string &$buffer, ?array &$completed, callable $onTextDelta): void
    {
        while (preg_match('/\r?\n\r?\n/', $buffer, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $matches[0][1];
            $separatorLength = strlen($matches[0][0]);
            $block = substr($buffer, 0, $offset);
            $buffer = substr($buffer, $offset + $separatorLength);
            $dataLines = [];

            foreach (preg_split('/\r?\n/', $block) ?: [] as $line) {
                if (str_starts_with($line, 'data:')) {
                    $dataLines[] = ltrim(substr($line, 5));
                }
            }

            $json = implode("\n", $dataLines);

            if ($json === '' || $json === '[DONE]') {
                continue;
            }

            $event = json_decode($json, true);

            if (! is_array($event)) {
                continue;
            }

            if (($event['type'] ?? null) === 'response.output_text.delta' && is_string($event['delta'] ?? null)) {
                $onTextDelta($event['delta']);
            }

            if (($event['type'] ?? null) === 'response.completed' && is_array($event['response'] ?? null)) {
                $completed = $event['response'];
            }

            if (in_array($event['type'] ?? null, ['response.failed', 'response.incomplete'], true)) {
                throw new AiAssistantException('OpenAI non ha completato la risposta in streaming.');
            }
        }
    }

    private function reportFailure(Response $response): void
    {
        Log::warning('OpenAI Responses API request failed.', [
            'status' => $response->status(),
            'request_id' => $response->header('x-request-id'),
            'error_type' => $response->json('error.type'),
            'error_code' => $response->json('error.code'),
            'error_message' => $response->json('error.message'),
        ]);
    }

    private function publicErrorMessage(Response $response): string
    {
        return match ($response->status()) {
            401, 403 => 'La chiave OpenAI non è valida o non è autorizzata per questo modello.',
            429 => 'Il limite di utilizzo OpenAI è stato raggiunto. Riprova tra poco.',
            400 => 'OpenAI ha rifiutato la richiesta. Controlla modello e configurazione.',
            default => $response->serverError()
                ? 'Il servizio OpenAI è temporaneamente indisponibile. Riprova tra poco.'
                : 'Non è stato possibile completare la richiesta AI.',
        };
    }
}
