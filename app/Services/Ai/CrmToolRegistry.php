<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\ContactStatus;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Goals\GoalResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Filament\Support\ItalianOptions;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Goal;
use App\Models\Practice;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CrmToolRegistry
{
    /** @return array<int, array<string, mixed>> */
    public function definitions(?array $only = null): array
    {
        $definitions = [
            $this->tool('get_client_rankings', 'Classifica i clienti con un criterio commerciale verificabile. Per "miglior cliente" usa commercial_value, basato sul valore effettivo delle pratiche completate dell’utente.', [
                'metric' => ['type' => 'string', 'enum' => ['commercial_value', 'managed_assets', 'completed_practices'], 'description' => 'Criterio: valore pratiche concluse, patrimonio gestito oppure numero di pratiche concluse.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di clienti, da 1 a 10.'],
            ]),
            $this->tool('get_prospect_outcomes', 'Conta i prospect unici con evidenze esplicite di mancata acquisizione. Non confonde il numero di prospect con il numero di pratiche e non include le aziende.', []),
            $this->tool('get_appointments', 'Recupera gli appuntamenti dell’utente in un intervallo di date. Usalo per oggi, domani, una giornata o un periodo.', [
                'from' => $this->nullableString('Data iniziale inclusa YYYY-MM-DD; null significa oggi.'),
                'to' => $this->nullableString('Data finale inclusa YYYY-MM-DD; null significa la data iniziale.'),
                'query' => $this->nullableString('Testo facoltativo da cercare nel titolo o nel soggetto.'),
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('search_contacts', 'Cerca clienti o prospect per nome, cognome, email, telefono o codice fiscale. Usalo prima dello storico quando non conosci l’ID.', [
                'query' => ['type' => 'string', 'description' => 'Nome o altro testo da cercare.'],
                'status' => ['type' => ['string', 'null'], 'enum' => ['client', 'prospect', null], 'description' => 'Filtra per client o prospect; null cerca entrambi.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('get_contact_history', 'Recupera il quadro completo e verificabile di un cliente o prospect: relationship_notes, informazioni importanti, note, timeline, appuntamenti, attività, pratiche e documenti. Usalo sempre dopo search_contacts quando l’utente nomina una persona o chiede preferenze, storico o dettagli.', [
                'contact_id' => ['type' => 'integer', 'description' => 'ID del contatto restituito da search_contacts.'],
            ]),
            $this->tool('search_companies', 'Cerca aziende per ragione sociale, partita IVA, codice fiscale, email o settore.', [
                'query' => ['type' => 'string', 'description' => 'Testo da cercare.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('get_company_history', 'Recupera dati, referenti, note, timeline, appuntamenti, attività e pratiche di un’azienda.', [
                'company_id' => ['type' => 'integer', 'description' => 'ID dell’azienda restituito da search_companies.'],
            ]),
            $this->tool('get_goal_progress', 'Recupera gli obiettivi sulle pratiche e calcola progresso, quantità mancante e percentuale.', [
                'status' => ['type' => ['string', 'null'], 'enum' => ['active', 'achieved', 'expired', 'cancelled', null], 'description' => 'Stato da filtrare; null restituisce tutti.'],
            ]),
            $this->tool('get_due_activities', 'Recupera attività scadute, di oggi o imminenti assegnate all’utente.', [
                'period' => ['type' => 'string', 'enum' => ['overdue', 'today', 'next_7_days', 'all_open'], 'description' => 'Periodo richiesto.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('get_practices', 'Cerca e filtra pratiche per testo e stato.', [
                'query' => $this->nullableString('Testo facoltativo da cercare in numero, titolo, contatto o azienda.'),
                'status' => ['type' => ['string', 'null'], 'enum' => ['draft', 'in_progress', 'waiting', 'completed', 'unsuccessful', 'cancelled', null], 'description' => 'Stato della pratica; null per tutti.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('get_expiring_documents', 'Recupera documenti già scaduti o in scadenza entro un numero di giorni.', [
                'days' => ['type' => 'integer', 'description' => 'Orizzonte da 0 a 365 giorni.'],
                'limit' => ['type' => 'integer', 'description' => 'Numero massimo di risultati, da 1 a 20.'],
            ]),
            $this->tool('get_crm_overview', 'Restituisce una panoramica corrente con conteggi di clienti, prospect, appuntamenti, attività, pratiche, documenti e obiettivi.', []),
        ];

        if ($only === null) {
            return $definitions;
        }

        $byName = collect($definitions)->keyBy('name');

        return collect($only)
            ->map(fn (string $name): ?array => $byName->get($name))
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $arguments */
    public function execute(string $name, array $arguments, User $user): array
    {
        return match ($name) {
            'get_appointments' => $this->appointments($arguments, $user),
            'search_contacts' => $this->contacts($arguments),
            'get_contact_history' => $this->contactHistory($arguments, $user),
            'search_companies' => $this->companies($arguments),
            'get_company_history' => $this->companyHistory($arguments, $user),
            'get_goal_progress' => $this->goals($arguments, $user),
            'get_due_activities' => $this->activities($arguments, $user),
            'get_practices' => $this->practices($arguments, $user),
            'get_expiring_documents' => $this->documents($arguments, $user),
            'get_crm_overview' => $this->overview($user),
            'get_client_rankings' => $this->clientRankings($arguments, $user),
            'get_prospect_outcomes' => $this->prospectOutcomes($user),
            default => throw new InvalidArgumentException("Strumento non disponibile: {$name}"),
        };
    }

    /** @param array<string, array<string, mixed>> $properties */
    private function tool(string $name, string $description, array $properties): array
    {
        return [
            'type' => 'function',
            'name' => $name,
            'description' => $description,
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => $properties === [] ? (object) [] : $properties,
                'required' => array_keys($properties),
                'additionalProperties' => false,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function nullableString(string $description): array
    {
        return ['type' => ['string', 'null'], 'description' => $description];
    }

    /** @param array<string, mixed> $arguments */
    private function appointments(array $arguments, User $user): array
    {
        $from = $this->date($arguments['from'] ?? null, today()->toImmutable());
        $to = $this->date($arguments['to'] ?? null, $from);

        if ($to->lessThan($from) || $from->diffInDays($to) > 366) {
            throw new InvalidArgumentException('Intervallo date non valido.');
        }

        $queryText = trim((string) ($arguments['query'] ?? ''));
        $appointments = Appointment::query()
            ->with(['contact', 'company', 'practice'])
            ->where('owner_id', $user->id)
            ->whereBetween('starts_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($queryText !== '', function (Builder $query) use ($queryText): void {
                $like = $this->like($queryText);
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhereHas('contact', fn (Builder $query): Builder => $query->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                        ->orWhereHas('company', fn (Builder $query): Builder => $query->where('name', 'like', $like));
                });
            })
            ->orderBy('starts_at')
            ->limit($this->limit($arguments))
            ->get();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'count' => $appointments->count(),
            'items' => $appointments->map(fn (Appointment $appointment): array => $this->appointmentData($appointment))->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function contacts(array $arguments): array
    {
        $text = trim((string) ($arguments['query'] ?? ''));

        if ($text === '') {
            throw new InvalidArgumentException('Inserisci un testo da cercare.');
        }

        $like = $this->like($text);
        $status = $arguments['status'] ?? null;
        $contacts = Contact::query()
            ->when($status, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('tax_code', 'like', $like);
            })
            ->orderBy('last_name')
            ->limit($this->limit($arguments))
            ->get();

        return [
            'count' => $contacts->count(),
            'items' => $contacts->map(fn (Contact $contact): array => $this->contactData($contact))->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function contactHistory(array $arguments, User $user): array
    {
        $contact = Contact::query()->findOrFail((int) ($arguments['contact_id'] ?? 0));
        $appointments = $contact->appointments()->with('notes')->where('owner_id', $user->id)->latest('starts_at')->limit(12)->get();
        $activities = $contact->activities()->where('owner_id', $user->id)->latest('created_at')->limit(12)->get();
        $practices = $contact->practices()->with('practiceType')->where('owner_id', $user->id)->latest('opened_at')->limit(12)->get();
        $notes = $contact->notes()->where('author_id', $user->id)->latest()->limit(15)->get();
        $timeline = $contact->timelineEvents()->where(fn (Builder $query): Builder => $query->whereNull('author_id')->orWhere('author_id', $user->id))->latest('occurred_at')->limit(20)->get();

        return [
            'contact' => [
                ...$this->contactData($contact),
                'source' => $this->value($contact->source),
                'first_contact_date' => $contact->first_contact_date?->toDateString(),
                'last_contact_at' => $contact->last_contact_at?->toIso8601String(),
                'next_follow_up_at' => $contact->next_follow_up_at?->toIso8601String(),
                'interests' => $this->translatedValues($contact->interests, ItalianOptions::INTERESTS),
                'personal_goals' => $this->translatedValues($contact->personal_goals, ItalianOptions::PERSONAL_GOALS),
                'important_information' => $this->text($contact->important_information),
                'relationship_notes' => $this->text($contact->relationship_notes),
            ],
            'appointments' => $appointments->map(fn (Appointment $appointment): array => [
                ...$this->appointmentData($appointment),
                'description' => $this->text($appointment->description),
                'emerged_need' => $this->text($appointment->emerged_need),
                'negative_reason' => ItalianOptions::NEGATIVE_REASONS[$appointment->negative_reason] ?? $appointment->negative_reason,
                'final_notes' => $this->text($appointment->final_notes),
                'reported_at' => $appointment->reported_at?->toIso8601String(),
                'notes' => $appointment->notes->map(fn ($note): array => ['title' => $note->title, 'content' => $this->text($note->content), 'date' => $note->created_at->toIso8601String()])->all(),
            ])->all(),
            'activities' => $activities->map(fn (Activity $activity): array => $this->activityData($activity))->all(),
            'practices' => $practices->map(fn (Practice $practice): array => $this->practiceData($practice))->all(),
            'documents' => $contact->documents()->where('uploaded_by_id', $user->id)->latest()->limit(12)->get()->map(fn (Document $document): array => $this->documentData($document))->all(),
            'companies' => $contact->companies()->get()->map(fn (Company $company): array => [...$this->companyData($company), 'role' => $company->pivot?->role])->all(),
            'notes' => $notes->map(fn ($note): array => ['title' => $note->title, 'content' => $this->text($note->content), 'important' => $note->is_important, 'date' => $note->created_at->toIso8601String()])->all(),
            'timeline' => $timeline->map(fn ($event): array => ['type' => $event->event_type, 'title' => $event->title, 'description' => $this->text($event->description), 'date' => $event->occurred_at->toIso8601String()])->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function companies(array $arguments): array
    {
        $text = trim((string) ($arguments['query'] ?? ''));

        if ($text === '') {
            throw new InvalidArgumentException('Inserisci un testo da cercare.');
        }

        $like = $this->like($text);
        $companies = Company::query()
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('vat_number', 'like', $like)
                    ->orWhere('tax_code', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('industry', 'like', $like);
            })
            ->orderBy('name')
            ->limit($this->limit($arguments))
            ->get();

        return ['count' => $companies->count(), 'items' => $companies->map(fn (Company $company): array => $this->companyData($company))->all()];
    }

    /** @param array<string, mixed> $arguments */
    private function companyHistory(array $arguments, User $user): array
    {
        $company = Company::query()->with('contacts')->findOrFail((int) ($arguments['company_id'] ?? 0));

        return [
            'company' => [...$this->companyData($company), 'opportunities' => $company->opportunities],
            'contacts' => $company->contacts->map(fn (Contact $contact): array => [...$this->contactData($contact), 'role' => $contact->pivot?->role])->all(),
            'appointments' => $company->appointments()->with(['contact', 'company', 'practice'])->where('owner_id', $user->id)->latest('starts_at')->limit(12)->get()->map(fn (Appointment $appointment): array => $this->appointmentData($appointment))->all(),
            'activities' => $company->activities()->where('owner_id', $user->id)->latest()->limit(12)->get()->map(fn (Activity $activity): array => $this->activityData($activity))->all(),
            'practices' => $company->practices()->with('practiceType')->where('owner_id', $user->id)->latest('opened_at')->limit(12)->get()->map(fn (Practice $practice): array => $this->practiceData($practice))->all(),
            'documents' => $company->documents()->where('uploaded_by_id', $user->id)->latest()->limit(12)->get()->map(fn (Document $document): array => $this->documentData($document))->all(),
            'notes' => $company->notes()->where('author_id', $user->id)->latest()->limit(15)->get()->map(fn ($note): array => ['title' => $note->title, 'content' => $this->text($note->content), 'important' => $note->is_important, 'date' => $note->created_at->toIso8601String()])->all(),
            'timeline' => $company->timelineEvents()->where(fn (Builder $query): Builder => $query->whereNull('author_id')->orWhere('author_id', $user->id))->latest('occurred_at')->limit(20)->get()->map(fn ($event): array => ['type' => $event->event_type, 'title' => $event->title, 'description' => $this->text($event->description), 'date' => $event->occurred_at->toIso8601String()])->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function goals(array $arguments, User $user): array
    {
        $goals = Goal::query()
            ->with('practiceType')
            ->where('owner_id', $user->id)
            ->when($arguments['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->orderBy('ends_at')
            ->get();

        return ['count' => $goals->count(), 'items' => $goals->map(function (Goal $goal): array {
            $current = $goal->current_quantity;

            return [
                'id' => $goal->id,
                'title' => $goal->title,
                'description' => $this->text($goal->description),
                'metric' => 'pratiche completate',
                'practice_type' => $goal->practiceType?->name,
                'status' => ItalianOptions::GOAL_STATUSES[$this->value($goal->status)] ?? $this->value($goal->status),
                'current_quantity' => $current,
                'target_quantity' => $goal->target_quantity,
                'remaining_quantity' => max(0, $goal->target_quantity - $current),
                'progress_percentage' => $goal->progress_percentage,
                'starts_at' => $goal->starts_at->toDateString(),
                'ends_at' => $goal->ends_at->toDateString(),
                'url' => GoalResource::getUrl('edit', ['record' => $goal], panel: 'admin'),
            ];
        })->all()];
    }

    /** @param array<string, mixed> $arguments */
    private function activities(array $arguments, User $user): array
    {
        $period = (string) ($arguments['period'] ?? 'overdue');
        $query = Activity::query()->with(['contact', 'company', 'practice'])->where('owner_id', $user->id)->open();

        match ($period) {
            'overdue' => $query->where('due_at', '<', now()),
            'today' => $query->whereDate('due_at', today()),
            'next_7_days' => $query->whereBetween('due_at', [now(), now()->addDays(7)->endOfDay()]),
            'all_open' => null,
            default => throw new InvalidArgumentException('Periodo attività non valido.'),
        };

        $activities = $query->orderByRaw('due_at is null')->orderBy('due_at')->limit($this->limit($arguments))->get();

        return ['period' => $period, 'count' => $activities->count(), 'items' => $activities->map(fn (Activity $activity): array => $this->activityData($activity))->all()];
    }

    /** @param array<string, mixed> $arguments */
    private function practices(array $arguments, User $user): array
    {
        $text = trim((string) ($arguments['query'] ?? ''));
        $query = Practice::query()->with(['practiceType', 'contact', 'company'])->where('owner_id', $user->id)
            ->when($arguments['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status));

        if ($text !== '') {
            $like = $this->like($text);
            $query->where(function (Builder $query) use ($like): void {
                $query->where('internal_number', 'like', $like)->orWhere('title', 'like', $like)
                    ->orWhereHas('contact', fn (Builder $query): Builder => $query->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('company', fn (Builder $query): Builder => $query->where('name', 'like', $like));
            });
        }

        $practices = $query->latest('opened_at')->limit($this->limit($arguments))->get();

        return ['count' => $practices->count(), 'items' => $practices->map(fn (Practice $practice): array => $this->practiceData($practice))->all()];
    }

    /** @param array<string, mixed> $arguments */
    private function documents(array $arguments, User $user): array
    {
        $days = max(0, min(365, (int) ($arguments['days'] ?? 30)));
        $documents = Document::query()->with(['contact', 'company', 'practice'])
            ->where('uploaded_by_id', $user->id)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', today()->addDays($days))
            ->orderBy('expires_at')
            ->limit($this->limit($arguments))
            ->get();

        return ['through' => today()->addDays($days)->toDateString(), 'count' => $documents->count(), 'items' => $documents->map(fn (Document $document): array => $this->documentData($document))->all()];
    }

    /** @param array<string, mixed> $arguments */
    private function clientRankings(array $arguments, User $user): array
    {
        $metric = (string) ($arguments['metric'] ?? 'commercial_value');

        if (! in_array($metric, ['commercial_value', 'managed_assets', 'completed_practices'], true)) {
            throw new InvalidArgumentException('Criterio di classifica non valido.');
        }

        $clients = Contact::query()
            ->clients()
            ->withCount([
                'practices as completed_practices_count' => fn (Builder $query): Builder => $query
                    ->where('owner_id', $user->id)
                    ->completed(),
            ])
            ->withSum([
                'practices as completed_practices_value' => fn (Builder $query): Builder => $query
                    ->where('owner_id', $user->id)
                    ->completed(),
            ], 'actual_value')
            ->get()
            ->map(function (Contact $contact) use ($metric): Contact {
                $contact->setAttribute('ranking_score', match ($metric) {
                    'managed_assets' => (float) ($contact->managed_assets ?? 0),
                    'completed_practices' => (int) $contact->completed_practices_count,
                    default => (float) ($contact->completed_practices_value ?? 0),
                });

                return $contact;
            })
            ->filter(fn (Contact $contact): bool => (float) $contact->getAttribute('ranking_score') > 0)
            ->sortByDesc(fn (Contact $contact): float|int => match ($metric) {
                'managed_assets' => (float) ($contact->managed_assets ?? 0),
                'completed_practices' => (int) $contact->completed_practices_count,
                default => (float) ($contact->completed_practices_value ?? 0),
            })
            ->take(max(1, min(10, (int) ($arguments['limit'] ?? 5))));

        return [
            'metric' => $metric,
            'metric_label' => match ($metric) {
                'managed_assets' => 'patrimonio gestito',
                'completed_practices' => 'numero di pratiche completate',
                default => 'valore effettivo delle pratiche completate',
            },
            'ranking_available' => $clients->isNotEmpty(),
            'unavailable_reason' => $clients->isEmpty() ? 'Nessun cliente possiede un valore positivo per la metrica richiesta. Non indicare un miglior cliente.' : null,
            'count' => $clients->count(),
            'items' => $clients->values()->map(fn (Contact $contact, int $index): array => [
                'rank' => $index + 1,
                ...$this->contactData($contact),
                'managed_assets' => (float) ($contact->managed_assets ?? 0),
                'completed_practices_count' => (int) $contact->completed_practices_count,
                'completed_practices_value' => (float) ($contact->completed_practices_value ?? 0),
            ])->all(),
        ];
    }

    private function prospectOutcomes(User $user): array
    {
        $prospects = Contact::query()
            ->prospects()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('practices', fn (Builder $query): Builder => $query
                        ->where('owner_id', $user->id)
                        ->where('status', 'unsuccessful'))
                    ->orWhereHas('appointments', fn (Builder $query): Builder => $query
                        ->where('owner_id', $user->id)
                        ->where('outcome', 'negative'));
            })
            ->with([
                'practices' => fn ($query) => $query
                    ->where('owner_id', $user->id)
                    ->where('status', 'unsuccessful')
                    ->latest('opened_at'),
                'appointments' => fn ($query) => $query
                    ->where('owner_id', $user->id)
                    ->where('outcome', 'negative')
                    ->latest('starts_at'),
                'activities' => fn ($query) => $query
                    ->where('owner_id', $user->id)
                    ->open()
                    ->orderByRaw('due_at IS NULL')
                    ->orderBy('due_at'),
            ])
            ->orderBy('last_name')
            ->get();

        return [
            'definition' => 'Prospect unici ancora nello stato prospect con almeno una pratica non conclusa o un appuntamento con esito negativo.',
            'current_prospects_total' => Contact::query()->prospects()->count(),
            'not_acquired_prospects_count' => $prospects->count(),
            'items' => $prospects->map(fn (Contact $contact): array => [
                ...$this->contactData($contact),
                'unsuccessful_practices_count' => $contact->practices->count(),
                'unsuccessful_practices' => $contact->practices->map(fn (Practice $practice): array => [
                    'title' => $practice->title,
                    'opened_at' => $practice->opened_at?->toDateString(),
                    'url' => PracticeResource::getUrl('edit', ['record' => $practice], panel: 'admin'),
                ])->all(),
                'negative_appointments_count' => $contact->appointments->count(),
                'negative_appointments' => $contact->appointments->map(fn (Appointment $appointment): array => [
                    'title' => $appointment->title,
                    'date' => $appointment->starts_at->toIso8601String(),
                    'negative_reason' => ItalianOptions::NEGATIVE_REASONS[$appointment->negative_reason] ?? $appointment->negative_reason,
                    'url' => AppointmentResource::getUrl('view', ['record' => $appointment], panel: 'admin'),
                ])->all(),
                'open_activities_count' => $contact->activities->count(),
                'overdue_activities_count' => $contact->activities->filter(fn (Activity $activity): bool => $activity->due_at !== null && $activity->due_at->isPast())->count(),
                'open_activities' => $contact->activities->take(5)->map(fn (Activity $activity): array => [
                    'title' => $activity->title,
                    'due_at' => $activity->due_at?->toIso8601String(),
                    'priority' => ItalianOptions::PRIORITIES[$this->value($activity->priority)] ?? $this->value($activity->priority),
                    'url' => ActivityResource::getUrl('edit', ['record' => $activity], panel: 'admin'),
                ])->all(),
            ])->all(),
        ];
    }

    private function overview(User $user): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'clients' => Contact::query()->clients()->count(),
            'prospects' => Contact::query()->prospects()->count(),
            'prospects_follow_up_due' => Contact::query()->prospects()->followUpDue()->count(),
            'appointments_today' => Appointment::query()->where('owner_id', $user->id)->whereDate('starts_at', today())->count(),
            'open_activities' => Activity::query()->where('owner_id', $user->id)->open()->count(),
            'overdue_activities' => Activity::query()->where('owner_id', $user->id)->due()->count(),
            'open_practices' => Practice::query()->where('owner_id', $user->id)->whereNotIn('status', ['completed', 'unsuccessful', 'cancelled'])->count(),
            'documents_expiring_30_days' => Document::query()->where('uploaded_by_id', $user->id)->whereBetween('expires_at', [today(), today()->addDays(30)])->count(),
            'active_goals' => Goal::query()->where('owner_id', $user->id)->where('status', 'active')->count(),
        ];
    }

    private function appointmentData(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'title' => $appointment->title,
            'subject' => $appointment->contact ? $this->contactName($appointment->contact) : $appointment->company?->name,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'mode' => ItalianOptions::APPOINTMENT_MODES[$appointment->mode] ?? $appointment->mode,
            'location' => $appointment->location,
            'appointment_status' => ItalianOptions::APPOINTMENT_STATUSES[$this->value($appointment->status)] ?? $this->value($appointment->status),
            'appointment_outcome' => ItalianOptions::APPOINTMENT_OUTCOMES[$this->value($appointment->outcome)] ?? $this->value($appointment->outcome),
            'url' => AppointmentResource::getUrl('view', ['record' => $appointment], panel: 'admin'),
        ];
    }

    private function contactData(Contact $contact): array
    {
        $status = $this->value($contact->status);
        $resource = $status === ContactStatus::Client->value ? ClientResource::class : ProspectResource::class;

        return [
            'id' => $contact->id,
            'name' => $this->contactName($contact),
            'status' => $status,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'priority' => ItalianOptions::PRIORITIES[$this->value($contact->priority)] ?? $this->value($contact->priority),
            'url' => $resource::getUrl('view', ['record' => $contact], panel: 'admin'),
        ];
    }

    private function companyData(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'vat_number' => $company->vat_number,
            'industry' => $company->industry,
            'email' => $company->email,
            'phone' => $company->phone,
            'url' => CompanyResource::getUrl('view', ['record' => $company], panel: 'admin'),
        ];
    }

    private function activityData(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $this->text($activity->description),
            'type' => ItalianOptions::ACTIVITY_TYPES[$this->value($activity->type)] ?? $this->value($activity->type),
            'subject' => $activity->contact ? $this->contactName($activity->contact) : $activity->company?->name,
            'practice' => $activity->practice?->title,
            'due_at' => $activity->due_at?->toIso8601String(),
            'status' => ItalianOptions::ACTIVITY_STATUSES[$this->value($activity->status)] ?? $this->value($activity->status),
            'outcome' => $this->text($activity->outcome),
            'notes' => $this->text($activity->notes),
            'url' => ActivityResource::getUrl('edit', ['record' => $activity], panel: 'admin'),
        ];
    }

    private function practiceData(Practice $practice): array
    {
        return [
            'id' => $practice->id,
            'number' => $practice->internal_number,
            'title' => $practice->title,
            'type' => $practice->practiceType?->name,
            'subject' => $practice->contact ? $this->contactName($practice->contact) : $practice->company?->name,
            'status' => ItalianOptions::PRACTICE_STATUSES[$this->value($practice->status)] ?? $this->value($practice->status),
            'priority' => ItalianOptions::PRIORITIES[$this->value($practice->priority)] ?? $this->value($practice->priority),
            'opened_at' => $practice->opened_at?->toDateString(),
            'expected_at' => $practice->expected_at?->toDateString(),
            'completed_at' => $practice->completed_at?->toDateString(),
            'expected_value' => $practice->expected_value,
            'actual_value' => $practice->actual_value,
            'outcome' => $practice->outcome,
            'notes' => $this->text($practice->notes),
            'url' => PracticeResource::getUrl('view', ['record' => $practice], panel: 'admin'),
        ];
    }

    private function documentData(Document $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'category' => $document->category,
            'subject' => $document->contact ? $this->contactName($document->contact) : $document->company?->name,
            'practice' => $document->practice?->title,
            'expires_at' => $document->expires_at?->toDateString(),
            'is_expired' => $document->expires_at?->isBefore(today()),
            'status' => ItalianOptions::DOCUMENT_STATUSES[$this->value($document->status)] ?? $this->value($document->status),
            'url' => DocumentResource::getUrl('edit', ['record' => $document], panel: 'admin'),
        ];
    }

    private function date(mixed $value, CarbonImmutable $default): CarbonImmutable
    {
        if (blank($value)) {
            return $default;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $value);

        if ($date === false || $date->toDateString() !== (string) $value) {
            throw new InvalidArgumentException('Data non valida.');
        }

        return $date;
    }

    /** @param array<string, mixed> $arguments */
    private function limit(array $arguments): int
    {
        return max(1, min(20, (int) ($arguments['limit'] ?? 10)));
    }

    private function like(string $value): string
    {
        return '%'.addcslashes($value, '%_\\').'%';
    }

    private function contactName(Contact $contact): string
    {
        return trim("{$contact->first_name} {$contact->last_name}");
    }

    /**
     * @param  array<int, string>|null  $values
     * @param  array<string, string>  $labels
     * @return array<int, string>
     */
    private function translatedValues(?array $values, array $labels): array
    {
        return collect($values ?? [])
            ->map(fn (string $value): string => $labels[$value] ?? $value)
            ->values()
            ->all();
    }

    private function text(mixed $value): ?string
    {
        return filled($value) ? Str::limit((string) $value, 1500) : null;
    }

    private function value(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return filled($value) ? (string) $value : null;
    }
}
