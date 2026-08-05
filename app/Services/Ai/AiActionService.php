<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiAction;
use App\Models\AiConversation;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\Practice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class AiActionService
{
    public const ALLOWED = ['create_contact', 'update_contact', 'create_appointment', 'update_practice', 'update_goal'];

    public function propose(AiConversation $conversation, User $user, string $action, array $payload): AiAction
    {
        if (! in_array($action, self::ALLOWED, true)) {
            throw ValidationException::withMessages(['action' => 'Azione non autorizzata.']);
        }

        if ($action === 'create_appointment' && filled($payload['starts_at'] ?? null) && blank($payload['ends_at'] ?? null)) {
            $payload['ends_at'] = Carbon::parse((string) $payload['starts_at'])->addHour()->toDateTimeString();
        }

        return AiAction::create(['user_id' => $user->id, 'ai_conversation_id' => $conversation->id, 'action' => $action, 'payload' => $payload, 'status' => 'pending']);
    }

    public function confirm(AiAction $item, User $user): AiAction
    {
        abort_unless($item->user_id === $user->id, 404);
        if ($item->status !== 'pending') {
            return $item;
        }
        $p = $item->payload;
        $result = match ($item->action) {
            'create_contact' => ['contact' => Contact::create([...Arr::only($p, ['first_name', 'last_name', 'email', 'phone', 'status', 'priority', 'profession', 'relationship_notes', 'important_information']), 'priority' => $p['priority'] ?? 'medium'])],
            'update_contact' => ['contact' => $this->owned(Contact::findOrFail((int) $p['id']), $user)->tap(fn (Contact $m) => $m->update(Arr::except($p, ['id'])))->fresh()],
            'create_appointment' => ['appointment' => $user->appointments()->create(Arr::only($p, ['title', 'description', 'contact_id', 'company_id', 'starts_at', 'ends_at', 'mode', 'location', 'status']))],
            'update_practice' => ['practice' => $this->owned(Practice::findOrFail((int) $p['id']), $user)->tap(fn (Practice $m) => $m->update(Arr::only($p, ['title', 'status', 'priority', 'expected_at', 'expected_value', 'actual_value', 'outcome', 'notes'])))->fresh()],
            'update_goal' => ['goal' => $this->owned(Goal::findOrFail((int) $p['id']), $user)->tap(fn (Goal $m) => $m->update(Arr::only($p, ['title', 'description', 'target_quantity', 'starts_at', 'ends_at', 'status'])))->fresh()],
        };
        $item->update(['status' => 'executed', 'result' => $result, 'confirmed_at' => now()]);

        return $item->fresh();
    }

    public function reject(AiAction $item, User $user): AiAction
    {
        abort_unless($item->user_id === $user->id, 404);
        if ($item->status === 'pending') {
            $item->update(['status' => 'rejected']);
        }

        return $item->fresh();
    }

    private function owned(object $model, User $user): object
    {
        abort_unless((int) $model->owner_id === $user->id, 404);

        return $model;
    }
}
