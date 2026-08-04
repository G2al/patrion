<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class DocumentController extends ApiController
{
    public function index(Request $request)
    {
        $query = $request->user()->uploadedDocuments()->with(['contact', 'company', 'practice'])->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))->when($request->string('category')->value(), fn ($q, $category) => $q->where('category', $category))->when($request->date('expires_from'), fn ($q, $date) => $q->whereDate('expires_at', '>=', $date))->when($request->date('expires_to'), fn ($q, $date) => $q->whereDate('expires_at', '<=', $date))->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))->orderBy('expires_at');

        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 50)))));
    }

    public function show(Request $request, Document $document)
    {
        abort_unless($document->uploaded_by_id === $request->user()->id, 404);

        return $this->ok(['document' => $document->load(['contact', 'company', 'practice'])]);
    }

    public function download(Request $request, Document $document)
    {
        abort_unless($document->uploaded_by_id === $request->user()->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        return Storage::disk($document->disk)->download($document->file_path, $document->name);
    }

    public function preview(Request $request, Document $document)
    {
        abort_unless($document->uploaded_by_id === $request->user()->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        return response()->stream(fn () => print Storage::disk($document->disk)->get($document->file_path), 200, ['Content-Type' => Storage::disk($document->disk)->mimeType($document->file_path) ?: 'application/octet-stream', 'Content-Disposition' => 'inline; filename="'.addslashes($document->name).'"']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'file' => ['required', 'file', 'max:20480'], 'category' => ['nullable', 'string'], 'description' => ['nullable', 'string'], 'contact_id' => ['nullable', 'exists:contacts,id'], 'company_id' => ['nullable', 'exists:companies,id'], 'practice_id' => ['nullable', 'exists:practices,id'], 'document_date' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date'], 'status' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]);
        $file = $request->file('file');
        $data['disk'] = 'local';
        $data['file_path'] = $file->store('documents');
        unset($data['file']);
        $document = $request->user()->uploadedDocuments()->create([...$data, 'status' => $data['status'] ?? 'valid']);

        return $this->ok(['document' => $document->load(['contact', 'company', 'practice'])], 201);
    }

    public function update(Request $request, Document $document)
    {
        abort_unless($document->uploaded_by_id === $request->user()->id, 404);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'category' => ['nullable', 'string'], 'description' => ['nullable', 'string'], 'expires_at' => ['nullable', 'date'], 'status' => ['nullable', 'string'], 'notes' => ['nullable', 'string']]);
        $document->update($data);

        return $this->ok(['document' => $document->fresh()->load(['contact', 'company', 'practice'])]);
    }

    public function destroy(Request $request, Document $document)
    {
        abort_unless($document->uploaded_by_id === $request->user()->id, 404);
        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        return $this->ok(['deleted' => true]);
    }
}
