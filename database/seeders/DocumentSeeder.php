<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Practice;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $practices = Practice::query()->where('internal_number', 'like', 'DEMO-PR-%')->orderBy('id')->get();
        $categories = ['Documento di identità', 'Contratto', 'Questionario', 'Estratto conto', 'Bilancio', 'Visura', 'Statuto'];

        foreach (range(1, 35) as $index) {
            $name = sprintf('documento-demo-%02d.txt', $index);
            $path = "demo-documents/{$name}";
            Storage::disk('local')->put($path, "Documento dimostrativo Patrion {$index}. Nessun dato personale reale.");

            Document::query()->firstOrCreate(['file_path' => $path], [
                'name' => $name, 'category' => $categories[$index % count($categories)], 'disk' => 'local',
                'description' => 'File demo innocuo per la verifica dello storage privato.',
                'contact_id' => $index % 7 === 0 ? null : $contacts[($index - 1) % $contacts->count()]->id,
                'company_id' => $index % 7 === 0 ? $companies[($index - 1) % $companies->count()]->id : null,
                'practice_id' => $index % 3 === 0 ? $practices[($index - 1) % $practices->count()]->id : null,
                'document_date' => today()->subDays($index * 3),
                'expires_at' => $index <= 6 ? today()->subDays($index) : ($index <= 16 ? today()->addDays($index - 6) : today()->addMonths(($index % 8) + 2)),
                'status' => $index <= 6 ? DocumentStatus::Expired : DocumentStatus::Valid, 'uploaded_by_id' => $owner->id,
            ]);
        }
    }
}
