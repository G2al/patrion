<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

final class NoteController extends ApiController
{
    public function storeForContact(Request $request, Contact $contact)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'content' => ['required', 'string'], 'is_important' => ['sometimes', 'boolean']]);
        $note = $contact->notes()->create([...$data, 'author_id' => $request->user()->id]);

        return $this->ok(['note' => $note], 201);
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->author_id === $request->user()->id, 404);
        $note->update($request->validate(['title' => ['sometimes', 'string', 'max:255'], 'content' => ['sometimes', 'string'], 'is_important' => ['sometimes', 'boolean']]));

        return $this->ok(['note' => $note->fresh()]);
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->author_id === $request->user()->id, 404);
        $note->delete();

        return $this->ok(['deleted' => true]);
    }
}
