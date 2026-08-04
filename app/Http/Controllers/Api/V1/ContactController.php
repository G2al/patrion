<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;

final class ContactController extends ApiController
{
    private const FIELDS = ['first_name', 'last_name', 'birth_date', 'birth_place', 'tax_code', 'profession', 'email', 'phone', 'whatsapp', 'status', 'first_contact_date', 'source', 'priority', 'potential_value', 'managed_assets', 'relationship_level', 'last_contact_at', 'next_follow_up_at', 'interests', 'personal_goals', 'personality_style', 'preferred_communication', 'contact_frequency', 'hobbies', 'family_information', 'birthdays', 'anniversaries', 'important_information', 'relationship_notes'];

    public function index(Request $request)
    {
        $query = Contact::query()->withCount(['appointments', 'activities', 'practices'])->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('priority')->value(), fn ($q, $priority) => $q->where('priority', $priority))->when($request->string('search')->value(), function ($q, $search): void {
            $like = '%'.$search.'%';
            $q->where(fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
        });

        return response()->json($query->orderBy('last_name')->paginate(min(50, max(1, $request->integer('per_page', 20)))));
    }

    public function show(Contact $contact)
    {
        return $this->ok(['contact' => $contact->load(['companies', 'appointments', 'activities', 'practices', 'documents', 'notes', 'timelineEvents'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['first_name' => ['required', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email'], 'status' => ['required', 'in:client,prospect'], 'priority' => ['nullable', 'in:low,medium,high']]);
        $contact = Contact::query()->create([...$data, 'priority' => $data['priority'] ?? 'medium']);

        return $this->ok(['contact' => $contact], 201);
    }

    public function update(Request $request, Contact $contact)
    {
        $request->validate(['first_name' => ['sometimes', 'string', 'max:255'], 'last_name' => ['sometimes', 'string', 'max:255'], 'email' => ['sometimes', 'nullable', 'email'], 'status' => ['sometimes', 'in:client,prospect'], 'priority' => ['sometimes', 'in:low,medium,high']]);
        $contact->update(array_intersect_key($request->all(), array_flip(self::FIELDS)));

        return $this->ok(['contact' => $contact->fresh()]);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return $this->ok(['deleted' => true]);
    }
}
