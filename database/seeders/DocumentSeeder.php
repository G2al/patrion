<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Practice;
use Illuminate\Support\Facades\Storage;

final class DocumentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        $practices = Practice::query()->get()->keyBy('internal_number');
        foreach ([
            ['name' => 'Documento identità Luigi Iommelli.txt', 'contact' => 'luigi.iommelli@example.test', 'practice' => 'PR-2026-001', 'category' => 'Documento di identità', 'expires_at' => today()->addYear()],
            ['name' => 'Questionario previdenziale Alessandra Costantini.txt', 'contact' => 'alessandra.costantini@example.test', 'practice' => 'PR-2026-003', 'category' => 'Questionario', 'expires_at' => today()->addMonths(6)],
            ['name' => 'Proposta protezione Giulia Bianchi.txt', 'contact' => 'giulia.bianchi@example.test', 'practice' => 'PR-2026-002', 'category' => 'Proposta', 'expires_at' => today()->addDays(14)],
        ] as $data) {
            $path = 'seed-documents/'.str_replace(' ', '-', strtolower($data['name']));
            Storage::disk('local')->put($path, "Documento dimostrativo coerente per {$data['name']}. Nessun dato personale reale.");
            Document::query()->updateOrCreate(['file_path' => $path], ['name' => $data['name'], 'category' => $data['category'], 'disk' => 'local', 'description' => 'Documento di esempio del fascicolo cliente.', 'contact_id' => $contacts[$data['contact']]->id, 'company_id' => null, 'practice_id' => $practices[$data['practice']]->id, 'document_date' => today()->subDays(5), 'expires_at' => $data['expires_at'], 'status' => DocumentStatus::Valid, 'uploaded_by_id' => $owner->id]);
        }
    }
}
