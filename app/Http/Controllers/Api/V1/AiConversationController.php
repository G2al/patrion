<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiToolCall;
use App\Services\Ai\AiAssistantException;
use App\Services\Ai\CrmAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

final class AiConversationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->aiConversations()
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json(['data' => $conversations]);
    }

    public function store(Request $request): JsonResponse
    {
        $conversation = $request->user()->aiConversations()->create([
            'title' => 'Nuova conversazione',
        ]);

        return $this->ok(['conversation' => $this->conversationData($conversation)], 201);
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $item = $this->ownedConversation($request, $conversation);

        return $this->ok([
            'conversation' => $this->conversationData($item),
            'messages' => $item->messages()->get()->map(fn (AiMessage $message): array => $this->messageData($message)),
        ]);
    }

    public function destroy(Request $request, int $conversation): JsonResponse
    {
        $this->ownedConversation($request, $conversation)->delete();

        return $this->ok(['deleted' => true]);
    }

    public function message(Request $request, int $conversation, CrmAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ], [
            'content.required' => 'Scrivi una domanda per l’assistente.',
            'content.max' => 'La domanda non può superare 4.000 caratteri.',
        ]);
        $item = $this->ownedConversation($request, $conversation);
        $content = trim($validated['content']);
        $userMessage = $item->messages()->create(['role' => 'user', 'content' => $content]);

        if ($item->title === 'Nuova conversazione') {
            $item->update(['title' => Str::limit($content, 70)]);
        }

        try {
            $answer = $assistant->answer($item, $request->user());
            $assistantMessage = $item->messages()->create([
                'role' => 'assistant',
                'content' => $answer['text'],
                'metadata' => $answer['metadata'],
            ]);

            AiToolCall::query()
                ->where('ai_conversation_id', $item->id)
                ->whereIn('id', $answer['tool_call_ids'])
                ->update(['ai_message_id' => $assistantMessage->id]);

            return $this->ok([
                'user_message' => $this->messageData($userMessage),
                'assistant_message' => $this->messageData($assistantMessage),
            ]);
        } catch (AiAssistantException $exception) {
            report($exception);
            $status = str_contains(Str::lower($exception->getMessage()), 'limite') ? 429 : 503;

            return $this->error($exception->getMessage(), $status);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Si è verificato un errore inatteso durante la richiesta AI.', 500);
        }
    }

    private function ownedConversation(Request $request, int $id): AiConversation
    {
        return $request->user()->aiConversations()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function conversationData(AiConversation $conversation): array
    {
        return $conversation->only(['id', 'title', 'created_at', 'updated_at']);
    }

    /** @return array<string, mixed> */
    private function messageData(AiMessage $message): array
    {
        return $message->only(['id', 'role', 'content', 'metadata', 'created_at']);
    }
}
