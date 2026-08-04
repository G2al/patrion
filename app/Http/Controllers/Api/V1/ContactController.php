<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ContactController extends ApiController
{
    private const FIELDS = [
        'first_name', 'last_name', 'birth_date', 'birth_place', 'tax_code',
        'identity_document_type', 'identity_document_number', 'identity_document_expires_at',
        'profession', 'marital_status', 'children_count', 'residence', 'domicile',
        'email', 'phone', 'whatsapp', 'status', 'first_contact_date', 'source',
        'referred_by_contact_id', 'priority', 'potential_value', 'managed_assets',
        'relationship_level', 'last_contact_at', 'next_follow_up_at', 'interests',
        'personal_goals', 'personality_style', 'preferred_communication',
        'contact_frequency', 'hobbies', 'family_information', 'birthdays',
        'anniversaries', 'important_information', 'relationship_notes',
    ];

    public function index(Request $request)
    {
        $query = Contact::query()->withCount(['appointments', 'activities', 'practices'])->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('priority')->value(), fn ($q, $priority) => $q->where('priority', $priority))->when($request->string('search')->value(), function ($q, $search): void {
            $like = '%'.$search.'%';
            $q->where(fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
        });

        return response()->json($query->orderBy('last_name')->paginate(min(50, max(1, $request->integer('per_page', 20))));
    }

    public function show(Contact $contact)
    {
        return $this->ok(['contact' => $contact->load(['referredBy', 'companies', 'appointments', 'activities', 'practices', 'documents', 'notes', 'timelineEvents'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        if ($photo !== null) {
            $data['photo_path'] = $photo->store('contact-photos', 'local');
        }

        $contact = Contact::query()->create([...array_intersect_key($data, array_flip(self::FIELDS)), 'priority' => $data['priority'] ?? 'medium']);

        return $this->ok(['contact' => $contact], 201);
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate($this->rules($contact, true));
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        if ($photo !== null) {
            if ($contact->photo_path) {
                Storage::disk('local')->delete($contact->photo_path);
            }
            $data['photo_path'] = $photo->store('contact-photos', 'local');
        }

        $contact->update(array_intersect_key($data, array_flip([...self::FIELDS, 'photo_path'])));

        return $this->ok(['contact' => $contact->fresh()]);
    }

    public function photo(Contact $contact): StreamedResponse
    {
        abort_unless($contact->photo_path && Storage::disk('local')->exists($contact->photo_path), 404);

        return Storage::disk('local')->response($contact->photo_path, headers: [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroy(Contact $contact)
    {
        if ($contact->photo_path) {
            Storage::disk('local')->delete($contact->photo_path);
        }
        $contact->delete();

        return $this->ok(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    private function rules(?Contact $contact = null, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'first_name' => [$required, 'string', 'max:255'],
            'last_name' => [$required, 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:16', Rule::unique('contacts', 'tax_code')->ignore($contact)],
            'identity_document_type' => ['nullable', 'string', 'max:255'],
            'identity_document_number' => ['nullable', 'string', 'max:255'],
            'identity_document_expires_at' => ['nullable', 'date'],
            'profession' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            'residence' => ['nullable', 'string'],
            'domicile' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'status' => [$required, 'in:client,prospect'],
            'first_contact_date' => ['nullable', 'date'],
            'source' => ['nullable', 'in:event,referral,linkedin,instagram,client,cold_call,other'],
            'referred_by_contact_id' => ['nullable', 'integer', 'exists:contacts,id', Rule::notIn(array_filter([$contact?->id]))],
            'priority' => ['nullable', 'in:low,medium,high'],
            'potential_value' => ['nullable', 'numeric', 'min:0'],
            'managed_assets' => ['nullable', 'numeric', 'min:0'],
            'relationship_level' => ['nullable', 'string', 'max:255'],
            'last_contact_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'interests' => ['nullable', 'array'], 'interests.*' => ['string', 'max:255'],
            'personal_goals' => ['nullable', 'array'], 'personal_goals.*' => ['string', 'max:255'],
            'personality_style' => ['nullable', 'string', 'max:255'],
            'preferred_communication' => ['nullable', 'in:phone,email,whatsapp,in_person'],
            'contact_frequency' => ['nullable', 'string', 'max:255'],
            'hobbies' => ['nullable', 'array'], 'hobbies.*' => ['string', 'max:255'],
            'family_information' => ['nullable', 'string'],
            'birthdays' => ['nullable', 'array'], 'birthdays.*' => ['string', 'max:255'],
            'anniversaries' => ['nullable', 'array'], 'anniversaries.*' => ['string', 'max:255'],
            'important_information' => ['nullable', 'string'],
            'relationship_notes' => ['nullable', 'string'],
        ];
    }
}
