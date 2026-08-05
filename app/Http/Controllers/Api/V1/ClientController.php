<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ClientController extends ApiController
{
    public function index(Request $request)
    {
        $type = $request->string('type')->value();
        abort_if($type !== '' && ! in_array($type, ['client', 'prospect', 'company'], true), 422, 'Tipologia cliente non valida.');
        $search = trim($request->string('search')->value());
        $contacts = in_array($type, ['', 'client', 'prospect'], true)
            ? Contact::query()->with('assignedUser')->when($type === 'client', fn ($q) => $q->where('status', ContactStatus::Client))->when($type === 'prospect', fn ($q) => $q->where('status', ContactStatus::Prospect))->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))->get()->map(fn (Contact $contact): array => $this->contactItem($contact))
            : collect();
        $companies = in_array($type, ['', 'company'], true)
            ? Company::query()->withCount(['contacts', 'appointments', 'practices'])->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))->get()->map(fn (Company $company): array => $this->companyItem($company))
            : collect();
        $items = $contacts->concat($companies)->sortBy(fn (array $item): string => mb_strtolower($item['name']))->values();
        $perPage = min(100, max(1, $request->integer('per_page', 20)));
        $page = max(1, $request->integer('page', 1));
        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return response()->json($paginator);
    }

    public function show(string $client)
    {
        [$type, $id] = $this->decode($client);
        if ($type === 'contact') {
            return $this->ok(['client' => ['entity_type' => 'contact', 'client_type' => Contact::findOrFail($id)->status?->value, 'record' => Contact::with(['assignedUser', 'companies', 'appointments', 'activities', 'practices', 'documents.uploadedBy', 'notes', 'timelineEvents', 'professionals', 'clientGoals'])->findOrFail($id)]]);
        }

        return $this->ok(['client' => ['entity_type' => 'company', 'client_type' => 'company', 'record' => Company::with(['contacts', 'appointments', 'activities', 'practices', 'documents.uploadedBy', 'notes', 'timelineEvents'])->findOrFail($id)]]);
    }

    private function contactItem(Contact $contact): array
    {
        return ['id' => "contact:{$contact->id}", 'entity_type' => 'contact', 'client_type' => $contact->status?->value, 'name' => trim("{$contact->first_name} {$contact->last_name}"), 'email' => $contact->email, 'phone' => $contact->phone, 'priority' => $contact->priority?->value, 'assigned_user' => $contact->assignedUser, 'record_id' => $contact->id];
    }

    private function companyItem(Company $company): array
    {
        return ['id' => "company:{$company->id}", 'entity_type' => 'company', 'client_type' => 'company', 'name' => $company->name, 'email' => $company->email, 'phone' => $company->phone, 'industry' => $company->industry, 'contacts_count' => $company->contacts_count, 'appointments_count' => $company->appointments_count, 'practices_count' => $company->practices_count, 'record_id' => $company->id];
    }

    private function decode(string $value): array
    {
        $parts = explode(':', $value, 2);
        abort_if(count($parts) !== 2 || ! in_array($parts[0], ['contact', 'company'], true) || ! ctype_digit($parts[1]), 404);

        return [$parts[0], (int) $parts[1]];
    }
}
