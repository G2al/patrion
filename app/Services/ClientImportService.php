<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\ContactStatus;
use App\Enums\DocumentStatus;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactGoal;
use App\Models\ContactProfessional;
use App\Models\Document;
use App\Models\Note;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

final class ClientImportService
{
    private const HEADERS = ['Categoria', 'Nome', 'Cognome/Ragione Sociale', 'Data nascita/Fondazione', 'Professione/Settore', 'Referente', 'Telefono', 'Email', 'Stato', 'Cliente dal', 'Fonte acquisizione', 'Ultimo contatto', 'Prossima attività', 'Famiglia', 'Professionisti collegati', 'Interessi', 'Obiettivi', 'Documenti', 'Pratiche', 'Note'];

    public function preview(UploadedFile $file, User $user): array
    {
        $rows = $this->parse($file);
        $items = [];
        foreach ($rows as $index => $row) {
            $items[] = $this->inspect($row, $index + 2);
        }

        return ['filename' => $file->getClientOriginalName(), 'rows' => $items, 'summary' => ['total' => count($items), 'valid' => collect($items)->where('valid', true)->count(), 'invalid' => collect($items)->where('valid', false)->count(), 'duplicates' => collect($items)->filter(fn ($item) => $item['duplicate'] !== null)->count()], 'duplicate_modes' => ['skip', 'update']];
    }

    public function import(UploadedFile $file, User $user, string $duplicateMode): array
    {
        if (! in_array($duplicateMode, ['skip', 'update'], true)) {
            throw ValidationException::withMessages(['duplicate_mode' => 'Usa skip oppure update.']);
        }
        $preview = $this->preview($file, $user);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'linked' => ['professionals' => 0, 'goals' => 0, 'practices' => 0, 'documents' => 0, 'activities' => 0]];
        DB::transaction(function () use ($preview, $duplicateMode, $user, &$result): void {
            foreach ($preview['rows'] as $item) {
                if (! $item['valid']) {
                    $result['errors'][] = ['row' => $item['row'], 'errors' => $item['errors']];

                    continue;
                }
                if ($item['duplicate'] !== null && $duplicateMode === 'skip') {
                    $result['skipped']++;

                    continue;
                }
                $record = $this->persist($item['normalized'], $user, $item['duplicate']['record_id'] ?? null);
                $item['duplicate'] === null ? $result['created']++ : $result['updated']++;
                $this->relations($record, $item['normalized'], $user, $result);
            }
        });

        return ['summary' => $result];
    }

    private function inspect(array $row, int $number): array
    {
        $normalized = $this->normalize($row);
        $errors = [];
        if (! in_array($normalized['category'], ['Privato', 'Prospect', 'Azienda'], true)) {
            $errors[] = 'Categoria non riconosciuta.';
        }
        if (blank($normalized['name'])) {
            $errors[] = 'Nome o ragione sociale mancante.';
        }
        $duplicate = $this->duplicate($normalized);

        return ['row' => $number, 'valid' => $errors === [], 'errors' => $errors, 'duplicate' => $duplicate, 'normalized' => $normalized, 'source' => $row];
    }

    private function normalize(array $row): array
    {
        $category = trim((string) ($row['Categoria'] ?? ''));
        $fullName = trim((string) ($row['Nome'] ?? ''));
        $surname = trim((string) ($row['Cognome/Ragione Sociale'] ?? ''));
        $name = $category === 'Azienda' ? $fullName : trim($fullName.' '.($surname === '-' ? '' : $surname));
        [$first, $last] = $category === 'Azienda' ? [$name, null] : $this->personName($name);

        $v = fn (string $key) => $row[$key] ?? $row[str_replace('attività', 'attivitÃ ', $key)] ?? null;
        return ['category' => $category, 'name' => $name, 'first_name' => $first, 'last_name' => $last, 'birth_date' => $this->date($v('Data nascita/Fondazione')), 'profession' => trim((string) $v('Professione/Settore')) ?: null, 'referent' => trim((string) $v('Referente')) ?: null, 'phone' => trim((string) $v('Telefono')) ?: null, 'email' => trim((string) $v('Email')) ?: null, 'status_label' => trim((string) $v('Stato')), 'first_contact_date' => $this->yearDate($v('Cliente dal')), 'source' => trim((string) $v('Fonte acquisizione')) ?: null, 'last_contact_at' => $this->date($v('Ultimo contatto'), true), 'next_activity' => trim((string) $v('Prossima attività')) ?: null, 'family' => trim((string) $v('Famiglia')) ?: null, 'professionals' => $this->split($v('Professionisti collegati')), 'interests' => $this->split($v('Interessi')), 'goals' => $this->split($v('Obiettivi')), 'documents' => $this->split($v('Documenti')), 'practices' => $this->split($v('Pratiche')), 'notes' => trim((string) $v('Note')) ?: null];
    }

    private function persist(array $data, User $user, ?int $existingId): Contact|Company
    {
        if ($data['category'] === 'Azienda') {
            if ($existingId) { $record = Company::query()->findOrFail($existingId); $record->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'industry' => $data['profession']]); return $record; }
            return Company::query()->create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'industry' => $data['profession']]);
        }

        $attributes = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?: '-', 'email' => $data['email'], 'phone' => $data['phone'], 'profession' => $data['profession'], 'status' => $data['category'] === 'Prospect' ? ContactStatus::Prospect : ContactStatus::Client, 'priority' => $this->priority($data['status_label']), 'first_contact_date' => $data['first_contact_date'], 'source' => $this->source($data['source']), 'last_contact_at' => $data['last_contact_at'], 'family_information' => $data['family'], 'interests' => $data['interests'], 'personal_goals' => $data['goals'], 'relationship_notes' => $data['notes'], 'assigned_user_id' => $user->id];
        if ($existingId) { $record = Contact::query()->findOrFail($existingId); $record->update($attributes); return $record; }
        return Contact::query()->create($attributes);
    }

    private function relations(Contact|Company $record, array $data, User $user, array &$result): void
    {
        if ($record instanceof Company) {
            if ($data['referent']) {
                [$first, $last] = $this->personName($data['referent']);
                $contact = Contact::query()->firstOrCreate(['first_name' => $first, 'last_name' => $last ?: '-'], ['status' => ContactStatus::Client, 'assigned_user_id' => $user->id]);
                $record->contacts()->syncWithoutDetaching([$contact->id => ['role' => 'Referente']]);
            }

return;
        }
        foreach ($data['professionals'] as $name) {
            ContactProfessional::query()->firstOrCreate(['contact_id' => $record->id, 'name' => $name], ['role' => 'Professionista collegato']);
            $result['linked']['professionals']++;
        }
        foreach ($data['goals'] as $title) {
            ContactGoal::query()->firstOrCreate(['contact_id' => $record->id, 'title' => $title], ['status' => 'planned', 'progress_percentage' => 0]);
            $result['linked']['goals']++;
        }
        $type = PracticeType::query()->first();
        foreach ($data['practices'] as $title) {
            if ($type) {
                Practice::query()->firstOrCreate(['owner_id' => $user->id, 'contact_id' => $record->id, 'title' => $title], ['internal_number' => 'IMP-'.str()->upper(str()->random(10)), 'practice_type_id' => $type->id, 'opened_at' => $data['first_contact_date'] ?? today(), 'status' => 'draft', 'priority' => Priority::Medium]);
                $result['linked']['practices']++;
            }
        }
        foreach ($data['documents'] as $name) {
            Document::query()->firstOrCreate(['uploaded_by_id' => $user->id, 'contact_id' => $record->id, 'name' => $name], ['category' => 'Importato', 'status' => DocumentStatus::Missing, 'file_path' => null, 'disk' => 'local', 'notes' => 'Documento da acquisire tramite importazione Excel.']);
            $result['linked']['documents']++;
        }
        if ($data['notes']) {
            Note::query()->firstOrCreate(['noteable_type' => Contact::class, 'noteable_id' => $record->id, 'title' => 'Nota importata'], ['content' => $data['notes'], 'author_id' => $user->id]);
        }
        if ($data['next_activity']) {
            Activity::query()->create(['title' => $data['next_activity'], 'type' => ActivityType::General, 'contact_id' => $record->id, 'due_at' => $this->extractActivityDate($data['next_activity']), 'status' => ActivityStatus::Pending, 'owner_id' => $user->id]);
            $result['linked']['activities']++;
        }
    }

    private function parse(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'File XLSX non leggibile.']);
        }
        $shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml) {
            $s = simplexml_load_string($xml);
            foreach ($s->si as $item) {
                $shared[] = (string) ($item->t ?? implode('', array_map('strval', iterator_to_array($item->r->t ?? []))));
            }
        }
        $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $rows = [];
        $headers = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                preg_match('/([A-Z]+)/', $ref, $m);
                $col = $this->columnIndex($m[1]);
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                }
                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? '';
                } $values[$col] = $value;
            } ksort($values);
            $values = array_values(array_pad($values, count(self::HEADERS), null));
            if ($headers === []) {
                $headers = $values;

                continue;
            } $rows[] = array_combine($headers, $values);
        }
        $zip->close();

        return $rows;
    }

    private function duplicate(array $data): ?array
    {
        if (blank($data['email']) && blank($data['phone'])) {
            return null;
        }
        if ($data['category'] === 'Azienda') {
            $company = Company::query()->where(fn ($q) => $q->when($data['email'], fn ($q) => $q->where('email', $data['email']))->when($data['phone'], fn ($q) => $q->orWhere('phone', $data['phone'])))->first();
            return $company ? ['entity_type' => 'company', 'record_id' => $company->id, 'name' => $company->name] : null;
        }
        $contact = Contact::query()->where(fn ($q) => $q->when($data['email'], fn ($q) => $q->where('email', $data['email']))->when($data['phone'], fn ($q) => $q->orWhere('phone', $data['phone'])))->first();
        if ($contact) {
            return ['entity_type' => 'contact', 'record_id' => $contact->id, 'name' => trim("{$contact->first_name} {$contact->last_name}")];
        }
        return null;
    }

    private function personName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? '', $parts[1] ?? null];
    }

    private function split(mixed $value): array
    {
        return collect(preg_split('/\s*;\s*|\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY))->reject(fn (string $v) => $v === '-')->values()->all();
    }

    private function date(mixed $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        } foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                return $endOfDay ? $date->endOfDay() : $date;
            } catch (\Throwable) {
            }
        }

return null;
    }

    private function yearDate(mixed $value): ?Carbon
    {
        $year = (int) $value;

        return $year > 1900 ? Carbon::create($year, 1, 1) : null;
    }

    private function priority(string $value): string
    {
        return match (mb_strtolower($value)) {
            'caldo', 'molto caldo' => Priority::High->value, 'da ricontattare', 'da contattare' => Priority::Medium->value, default => Priority::Medium->value
        };
    }

    private function source(?string $value): ?string
    {
        return match (mb_strtolower((string) $value)) {
            'referral' => 'referral', 'evento' => 'event', 'linkedin' => 'linkedin', 'cliente' => 'client', default => 'other'
        };
    }

    private function extractActivityDate(string $value): Carbon
    {
        preg_match('/(\d{1,2})\/(\d{1,2})/', $value, $m);

        return isset($m[1]) ? Carbon::create(now()->year, (int) $m[2], (int) $m[1], 9) : now()->addDay();
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

return $index - 1;
    }
}
