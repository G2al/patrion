<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;

final class AppointmentController extends ApiController
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $query = $user->appointments()->with(['contact', 'company', 'practice'])->when($request->date('from'), fn ($q, $from) => $q->whereDate('starts_at', '>=', $from))->when($request->date('to'), fn ($q, $to) => $q->whereDate('starts_at', '<=', $to))->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('mode')->value(), fn ($q, $mode) => $q->where('mode', $mode))->when($request->integer('contact_id'), fn ($q, $id) => $q->where('contact_id', $id))->when($request->integer('company_id'), fn ($q, $id) => $q->where('company_id', $id))->orderBy('starts_at');

        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 50)))));
    }

    public function show(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->owner_id === $request->user()->id, 404);

        return $this->ok(['appointment' => $appointment->load(['contact', 'company', 'practice', 'activities', 'notes'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'contact_id' => ['nullable', 'exists:contacts,id'], 'company_id' => ['nullable', 'exists:companies,id'], 'practice_id' => ['nullable', 'exists:practices,id'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['nullable', 'string'], 'mode' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'outcome' => ['nullable', 'string'], 'final_notes' => ['nullable', 'string']]);
        abort_if(blank($data['contact_id'] ?? null) && blank($data['company_id'] ?? null), 422, 'Indica un contatto o un’azienda.');
        abort_if(filled($data['contact_id'] ?? null) && filled($data['company_id'] ?? null), 422, 'Indica un solo soggetto principale.');
        $appointment = Appointment::query()->create([...$data, 'owner_id' => $request->user()->id, 'status' => $data['status'] ?? 'scheduled']);

        return $this->ok(['appointment' => $appointment->load(['contact', 'company', 'practice'])], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->owner_id === $request->user()->id, 404);
        $appointment->update($request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'starts_at' => ['sometimes', 'date'], 'ends_at' => ['sometimes', 'date', 'after:starts_at'], 'location' => ['nullable', 'string'], 'mode' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'outcome' => ['nullable', 'string'], 'final_notes' => ['nullable', 'string']]));

        return $this->ok(['appointment' => $appointment->fresh()->load(['contact', 'company', 'practice'])]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->owner_id === $request->user()->id, 404);
        $appointment->delete();

        return $this->ok(['deleted' => true]);
    }
}
