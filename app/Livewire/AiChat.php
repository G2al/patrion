<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AiConversation;
use App\Models\AiToolCall;
use App\Models\User;
use App\Services\Ai\AiAssistantException;
use App\Services\Ai\CrmAssistant;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

class AiChat extends Component
{
    public bool $isOpen = false;

    public bool $showHistory = false;

    public ?int $conversationId = null;

    public string $message = '';

    public function mount(): void
    {
        $this->conversationId = $this->user()->aiConversations()->latest('updated_at')->value('id');
    }

    public function render(): View
    {
        return view('livewire.ai-chat');
    }

    public function openChat(): void
    {
        $this->isOpen = true;
        $this->showHistory = false;
        $this->dispatch('ai-scroll');
    }

    public function closeChat(): void
    {
        $this->isOpen = false;
        $this->showHistory = false;
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function conversations(): Collection
    {
        return $this->user()->aiConversations()->latest('updated_at')->limit(30)->get();
    }

    public function conversationMessages(): Collection
    {
        if ($this->conversationId === null) {
            return new Collection;
        }

        return $this->conversation()->messages()->get();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->conversationId = $this->user()->aiConversations()->findOrFail($conversationId)->id;
        $this->showHistory = false;
        $this->resetValidation();
        $this->dispatch('ai-scroll');
    }

    public function newConversation(): void
    {
        $this->conversationId = null;
        $this->message = '';
        $this->showHistory = false;
        $this->resetValidation();
    }

    public function deleteConversation(int $conversationId): void
    {
        $conversation = $this->user()->aiConversations()->findOrFail($conversationId);
        $wasSelected = $conversation->id === $this->conversationId;
        $conversation->delete();

        if ($wasSelected) {
            $this->conversationId = $this->user()->aiConversations()->latest('updated_at')->value('id');
        }

        Notification::make()->success()->title('Conversazione eliminata')->send();
    }

    public function useSuggestion(string $suggestion): void
    {
        $this->message = Str::limit(trim($suggestion), 4000, '');
    }

    public function askSuggestion(string $suggestion): void
    {
        $this->useSuggestion($suggestion);
        $this->sendMessage($suggestion);
    }

    public function sendMessage(?string $submittedMessage = null): void
    {
        if ($submittedMessage !== null) {
            $this->message = Str::limit(trim($submittedMessage), 4000, '');
        }

        $validated = $this->validate([
            'message' => ['required', 'string', 'max:4000'],
        ], [
            'message.required' => 'Scrivi una domanda per l’assistente.',
            'message.max' => 'La domanda non può superare 4.000 caratteri.',
        ]);

        $this->isOpen = true;
        $this->showHistory = false;
        $conversation = $this->conversationId === null
            ? $this->user()->aiConversations()->create(['title' => Str::limit($validated['message'], 70)])
            : $this->conversation();

        $this->conversationId = $conversation->id;
        $conversation->messages()->create([
            'role' => 'user',
            'content' => trim($validated['message']),
        ]);
        $this->message = '';

        try {
            $this->stream(to: 'ai-status', content: 'Analizzo la richiesta…', replace: true);
            $answer = app(CrmAssistant::class)->answer(
                $conversation,
                $this->user(),
                fn (string $delta) => $this->stream(to: 'ai-answer', content: e($this->streamPreview($delta))),
                fn (string $status) => $this->stream(to: 'ai-status', content: e($status), replace: true),
            );
            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $answer['text'],
                'metadata' => $answer['metadata'],
            ]);

            AiToolCall::query()
                ->where('ai_conversation_id', $conversation->id)
                ->whereIn('id', $answer['tool_call_ids'])
                ->update(['ai_message_id' => $assistantMessage->id]);
        } catch (Throwable $exception) {
            report($exception);
            $publicMessage = $exception instanceof AiAssistantException
                ? $exception->getMessage()
                : 'Si è verificato un errore inatteso durante la richiesta.';

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => "⚠️ {$publicMessage}",
                'metadata' => ['error' => true],
            ]);

            Notification::make()->danger()->title('Richiesta AI non completata')->body($publicMessage)->send();
        }

        $this->dispatch('ai-scroll');
    }

    private function conversation(): AiConversation
    {
        return $this->user()->aiConversations()->findOrFail($this->conversationId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function streamPreview(string $delta): string
    {
        return str_replace(['**', '__', '```', '`', '###', '##', '#', '[', ']'], '', $delta);
    }
}
