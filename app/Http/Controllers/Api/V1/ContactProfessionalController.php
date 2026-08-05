<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use App\Models\ContactProfessional;
use Illuminate\Http\Request;

final class ContactProfessionalController extends ApiController
{
    public function index(Contact $contact)
    {
        return $this->ok(['professionals' => $contact->professionals()->latest()->get()]);
    }

    public function store(Request $request, Contact $contact)
    {
        $item = $contact->professionals()->create($request->validate(['name' => ['required', 'string', 'max:255'], 'role' => ['required', 'string', 'max:255'], 'company_name' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string']]));

        return $this->ok(['professional' => $item], 201);
    }

    public function update(Request $request, ContactProfessional $professional)
    {
        $item = $professional->update($request->validate(['name' => ['sometimes', 'string', 'max:255'], 'role' => ['sometimes', 'string', 'max:255'], 'company_name' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string']]));

        return $this->ok(['professional' => $professional->fresh()]);
    }

    public function destroy(ContactProfessional $professional)
    {
        $professional->delete();

        return $this->ok(['deleted' => true]);
    }
}
