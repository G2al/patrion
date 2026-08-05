<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Email;
use Illuminate\Http\Request;

final class EmailController extends ApiController
{
    public function index(Request $request)
    {
        $query = $request->user()->emails()->with('contact')
            ->when($request->has('is_read'), fn ($q) => $q->where('is_read', $request->boolean('is_read')))
            ->when($request->has('is_important'), fn ($q) => $q->where('is_important', $request->boolean('is_important')))
            ->when($request->string('direction')->value(), fn ($q, $v) => $q->where('direction', $v))
            ->when($request->integer('contact_id'), fn ($q, $v) => $q->where('contact_id', $v))
            ->when($request->string('search')->value(), function ($q, $v): void {
                $like = '%'.$v.'%';
                $q->where(fn ($q) => $q->where('subject', 'like', $like)->orWhere('sender_name', 'like', $like)->orWhere('sender_email', 'like', $like)->orWhere('recipient_email', 'like', $like)->orWhere('body', 'like', $like));
            })->orderByDesc('received_at')->orderByDesc('created_at');

        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 20)))));
    }

    public function show(Request $request, Email $email)
    {
        $this->owned($request, $email);

        return $this->ok(['email' => $email->load('contact')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['preview'] ??= mb_substr(trim(strip_tags($data['body'])), 0, 180);

        return $this->ok(['email' => $request->user()->emails()->create($data)->load('contact')], 201);
    }

    public function update(Request $request, Email $email)
    {
        $this->owned($request, $email);
        $email->update($request->validate($this->rules(true)));

        return $this->ok(['email' => $email->fresh()->load('contact')]);
    }

    public function destroy(Request $request, Email $email)
    {
        $this->owned($request, $email);
        $email->delete();

        return $this->ok(['deleted' => true]);
    }

    public function read(Request $request, Email $email)
    {
        $this->owned($request, $email);
        $email->update(['is_read' => true]);

        return $this->ok(['email' => $email->fresh()->load('contact')]);
    }

    private function owned(Request $request, Email $email): void
    {
        abort_unless($email->user_id === $request->user()->id, 404);
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return ['contact_id' => ['nullable', 'exists:contacts,id'], 'sender_name' => [$required, 'string', 'max:255'], 'sender_email' => [$required, 'email', 'max:255'], 'recipient_email' => [$required, 'email', 'max:255'], 'subject' => [$required, 'string', 'max:255'], 'body' => [$required, 'string'], 'preview' => ['nullable', 'string'], 'direction' => [$required, 'in:incoming,outgoing'], 'is_read' => ['nullable', 'boolean'], 'is_important' => ['nullable', 'boolean'], 'received_at' => ['nullable', 'date'], 'sent_at' => ['nullable', 'date']];
    }
}
