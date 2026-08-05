<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use App\Models\ContactGoal;
use Illuminate\Http\Request;

final class ContactGoalController extends ApiController
{
    public function index(Contact $contact)
    {
        return $this->ok(['client_goals' => $contact->clientGoals()->orderBy('due_date')->get()]);
    }

    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate($this->rules());

        return $this->ok(['goal' => $contact->clientGoals()->create($data)], 201);
    }

    public function update(Request $request, ContactGoal $goal)
    {
        $goal->update($request->validate($this->rules(true)));

        return $this->ok(['goal' => $goal->fresh()]);
    }

    public function destroy(ContactGoal $goal)
    {
        $goal->delete();

        return $this->ok(['deleted' => true]);
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return ['title' => [$required, 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['nullable', 'in:planned,in_progress,completed,cancelled'], 'due_date' => ['nullable', 'date'], 'progress_percentage' => ['nullable', 'integer', 'min:0', 'max:100']];
    }
}
