<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Practice;
use Illuminate\Http\Request;

final class SearchController extends ApiController
{
    public function __invoke(Request $request)
    {
        $term = trim($request->string('q')->value());
        abort_if($term === '', 422, 'Il parametro q è obbligatorio.');
        $like = '%'.$term.'%';
        $userId = $request->user()->id;

        return $this->ok([
            'query' => $term,
            'contacts' => Contact::query()->where(fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like))->limit(8)->get(),
            'companies' => Company::query()->where('name', 'like', $like)->limit(8)->get(),
            'practices' => Practice::query()->where('owner_id', $userId)->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('internal_number', 'like', $like))->with(['contact', 'company'])->limit(8)->get(),
            'appointments' => Appointment::query()->where('owner_id', $userId)->where('title', 'like', $like)->with(['contact', 'company'])->limit(8)->get(),
            'documents' => Document::query()->where('uploaded_by_id', $userId)->where('name', 'like', $like)->with(['contact', 'company'])->limit(8)->get(),
        ]);
    }
}
