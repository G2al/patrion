<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Goal;
use Illuminate\Http\Request;

final class GoalController extends ApiController
{
    public function index(Request $request)
    {
        $period = $request->string('period')->value();
        [$from, $to] = match ($period) {
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'semiannual' => [now()->month <= 6 ? now()->startOfYear() : now()->startOfYear()->addMonths(6), now()->month <= 6 ? now()->startOfYear()->addMonths(5)->endOfMonth() : now()->endOfYear()],
            default => [$request->date('from'), $request->date('to')],
        };
        $goals = $request->user()->goals()->with('practiceType')->when($from, fn ($q) => $q->whereDate('starts_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('ends_at', '<=', $to))->orderBy('ends_at')->get();

        return $this->ok(['goals' => $goals->map(fn ($goal): array => [
            'id' => $goal->id,
            'name' => $goal->title,
            'status' => $goal->status?->value,
            'starts_at' => $goal->starts_at?->toDateString(),
            'ends_at' => $goal->ends_at?->toDateString(),
            'target_quantity' => $goal->target_quantity,
            'current_quantity' => $goal->current_quantity,
            'remaining_quantity' => max(0, $goal->target_quantity - $goal->current_quantity),
            'progress_percentage' => $goal->progress_percentage,
            'practice_type' => $goal->practiceType,
        ])->all()]);
    }

    public function show(Request $request, Goal $goal)
    {
        abort_unless($goal->owner_id === $request->user()->id, 404);

        return $this->ok(['goal' => [
            'id' => $goal->id,
            'name' => $goal->title,
            'status' => $goal->status?->value,
            'starts_at' => $goal->starts_at?->toDateString(),
            'ends_at' => $goal->ends_at?->toDateString(),
            'target_quantity' => $goal->target_quantity,
            'current_quantity' => $goal->current_quantity,
            'remaining_quantity' => max(0, $goal->target_quantity - $goal->current_quantity),
            'progress_percentage' => $goal->progress_percentage,
            'practice_type' => $goal->practiceType,
        ]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'practice_type_id' => ['required', 'exists:practice_types,id'], 'target_quantity' => ['required', 'integer', 'min:1'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after_or_equal:starts_at'], 'status' => ['nullable', 'string']]);
        $goal = $request->user()->goals()->create([...$data, 'status' => $data['status'] ?? 'active']);

        return $this->ok(['goal' => $goal->load('practiceType')], 201);
    }

    public function update(Request $request, Goal $goal)
    {
        abort_unless($goal->owner_id === $request->user()->id, 404);
        $goal->update($request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'target_quantity' => ['sometimes', 'integer', 'min:1'], 'starts_at' => ['sometimes', 'date'], 'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'], 'status' => ['nullable', 'string']]));

        return $this->ok(['goal' => $goal->fresh()->load('practiceType')]);
    }

    public function destroy(Request $request, Goal $goal)
    {
        abort_unless($goal->owner_id === $request->user()->id, 404);
        $goal->delete();

        return $this->ok(['deleted' => true]);
    }
}
