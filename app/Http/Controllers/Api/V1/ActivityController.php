<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

final class ActivityController extends ApiController
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $query = $user->activities()->with(['contact', 'company', 'practice', 'appointment'])->when($request->boolean('open'), fn ($q) => $q->open())->when($request->boolean('overdue'), fn ($q) => $q->due())->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('priority')->value(), fn ($q, $priority) => $q->where('priority', $priority))->when($request->date('from'), fn ($q, $from) => $q->whereDate('due_at', '>=', $from))->when($request->date('to'), fn ($q, $to) => $q->whereDate('due_at', '<=', $to))->when($request->string('search')->value(), fn ($q, $search) => $q->where('title', 'like', '%'.$search.'%'))->orderByRaw('due_at IS NULL')->orderBy('due_at');

        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 50)))));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'type' => ['nullable', 'string'], 'contact_id' => ['nullable', 'exists:contacts,id'], 'company_id' => ['nullable', 'exists:companies,id'], 'practice_id' => ['nullable', 'exists:practices,id'], 'appointment_id' => ['nullable', 'exists:appointments,id'], 'scheduled_at' => ['nullable', 'date'], 'due_at' => ['nullable', 'date'], 'priority' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'outcome' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]);
        $activity = $request->user()->activities()->create([...$data, 'status' => $data['status'] ?? 'pending', 'priority' => $data['priority'] ?? 'medium']);

        return $this->ok(['activity' => $activity->load(['contact', 'company', 'practice', 'appointment'])], 201);
    }

    public function update(Request $request, Activity $activity)
    {
        abort_unless($activity->owner_id === $request->user()->id, 404);
        $activity->update($request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'due_at' => ['nullable', 'date'], 'priority' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'outcome' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]));

        return $this->ok(['activity' => $activity->fresh()->load(['contact', 'company', 'practice', 'appointment'])]);
    }

    public function destroy(Request $request, Activity $activity)
    {
        abort_unless($activity->owner_id === $request->user()->id, 404);
        $activity->delete();

        return $this->ok(['deleted' => true]);
    }
}
