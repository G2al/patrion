<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Practice;
use Illuminate\Http\Request;

final class PracticeController extends ApiController
{
    public function index(Request $request)
    {
        $query = $request->user()->practices()->with(['contact', 'company', 'practiceType', 'activities', 'documents'])->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('priority')->value(), fn ($q, $priority) => $q->where('priority', $priority))->when($request->integer('contact_id'), fn ($q, $id) => $q->where('contact_id', $id))->when($request->integer('company_id'), fn ($q, $id) => $q->where('company_id', $id))->when($request->string('search')->value(), fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('internal_number', 'like', '%'.$search.'%')))->orderByDesc('opened_at');

        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 50)))));
    }

    public function show(Request $request, Practice $practice)
    {
        abort_unless($practice->owner_id === $request->user()->id, 404);

        return $this->ok(['practice' => $practice->load(['contact', 'company', 'practiceType', 'activities', 'documents', 'notes', 'appointments'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'practice_type_id' => ['required', 'exists:practice_types,id'], 'contact_id' => ['nullable', 'exists:contacts,id'], 'company_id' => ['nullable', 'exists:companies,id'], 'status' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'opened_at' => ['required', 'date'], 'expected_at' => ['nullable', 'date'], 'expected_value' => ['nullable', 'numeric'], 'notes' => ['nullable', 'string']]);
        abort_if(blank($data['contact_id'] ?? null) === blank($data['company_id'] ?? null), 422, 'Indica esattamente un contatto oppure un’azienda.');
        $practice = $request->user()->practices()->create([...$data, 'internal_number' => 'PR-'.now()->format('YmdHis').'-'.random_int(100, 999), 'status' => $data['status'] ?? 'draft', 'priority' => $data['priority'] ?? 'medium']);

        return $this->ok(['practice' => $practice->load(['contact', 'company', 'practiceType'])], 201);
    }

    public function update(Request $request, Practice $practice)
    {
        abort_unless($practice->owner_id === $request->user()->id, 404);
        $practice->update($request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'priority' => ['nullable', 'string'], 'expected_at' => ['nullable', 'date'], 'expected_value' => ['nullable', 'numeric'], 'actual_value' => ['nullable', 'numeric'], 'outcome' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]));

        return $this->ok(['practice' => $practice->fresh()->load(['contact', 'company', 'practiceType'])]);
    }

    public function destroy(Request $request, Practice $practice)
    {
        abort_unless($practice->owner_id === $request->user()->id, 404);
        $practice->delete();

        return $this->ok(['deleted' => true]);
    }
}
