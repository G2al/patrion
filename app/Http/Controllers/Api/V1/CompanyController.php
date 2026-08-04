<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use Illuminate\Http\Request;

final class CompanyController extends ApiController
{
    public function index(Request $request)
    {
        $query = Company::query()->withCount(['contacts', 'appointments', 'activities', 'practices'])->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))->orderBy('name');

        return response()->json($query->paginate(min(50, max(1, $request->integer('per_page', 20)))));
    }

    public function show(Company $company)
    {
        return $this->ok(['company' => $company->load(['contacts', 'appointments', 'activities', 'practices', 'documents', 'notes', 'timelineEvents'])]);
    }

    public function store(Request $request)
    {
        $company = Company::query()->create($request->validate(['name' => ['required', 'string', 'max:255'], 'vat_number' => ['nullable', 'string', 'max:32'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'industry' => ['nullable', 'string']]));

        return $this->ok(['company' => $company], 201);
    }

    public function update(Request $request, Company $company)
    {
        $company->update($request->validate(['name' => ['sometimes', 'string', 'max:255'], 'vat_number' => ['sometimes', 'nullable', 'string', 'max:32'], 'email' => ['sometimes', 'nullable', 'email'], 'phone' => ['sometimes', 'nullable', 'string'], 'industry' => ['sometimes', 'nullable', 'string']]));

        return $this->ok(['company' => $company->fresh()]);
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return $this->ok(['deleted' => true]);
    }

    public function attachContact(Request $request, Company $company)
    {
        $data = $request->validate(['contact_id' => ['required', 'exists:contacts,id'], 'role' => ['nullable', 'string', 'max:255']]);
        $company->contacts()->syncWithoutDetaching([$data['contact_id'] => ['role' => $data['role'] ?? null]]);

        return $this->ok(['attached' => true]);
    }

    public function detachContact(Company $company, int $contact)
    {
        $company->contacts()->detach($contact);

        return $this->ok(['detached' => true]);
    }
}
