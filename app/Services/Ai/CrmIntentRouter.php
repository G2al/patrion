<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Str;

final class CrmIntentRouter
{
    /** @return array{mode: string, tools: array<int, string>, guidance: string} */
    public function route(string $message, bool $hasPendingActionContext = false): array
    {
        $normalized = Str::of($message)->ascii()->lower()->squish()->toString();

        if ($this->isConversational($normalized)) {
            return [
                'mode' => 'conversation',
                'tools' => [],
                'guidance' => 'La richiesta è conversazionale e non richiede dati del CRM. Rispondi brevemente e con naturalezza, senza richiamare né ripetere risultati precedenti.',
            ];
        }

        $tools = [];
        $advisory = $this->containsAny($normalized, ['analizz', 'consigli', 'migliorare', 'opportunit', 'rischi', 'evitare', 'cosa posso fare', 'come gestire', 'strategia', 'consulenzial', 'copilota', 'proposta']);
        $actionRequest = $hasPendingActionContext || $this->containsAny($normalized, ['crea ', 'creare ', 'aggiungi ', 'modifica ', 'cambia ', 'aggiorna ', 'completa ', 'fissami ', 'fissa ', 'elimina ']);
        $this->addWhen($tools, $normalized, ['appuntament', 'agenda', 'calendario', 'incontr', 'telefonat'], ['get_appointments']);
        $this->addWhen($tools, $normalized, ['obiettiv', 'target', 'traguard'], ['get_goal_progress']);
        $this->addWhen($tools, $normalized, ['attivit', 'task', 'scadut', 'promemoria', 'cosa devo fare'], ['get_due_activities']);
        $this->addWhen($tools, $normalized, ['pratic', 'polizz', 'contratt'], ['get_practices']);
        $this->addWhen($tools, $normalized, ['document', 'allegat', 'scadenz'], ['get_expiring_documents']);
        $this->addWhen($tools, $normalized, ['aziend', 'societ', 'impresa'], ['search_companies', 'get_company_history']);

        $isNamedAppointmentRequest = $this->containsAny($normalized, ['fissami', 'fissa', 'prenota', 'organizza']) && Str::contains($normalized, ' con ');
        if ($isNamedAppointmentRequest || ($this->containsAny($normalized, ['appuntament', 'incontr']) && $this->containsAny($normalized, ['preferenz', 'note relaz', 'relazional']))) {
            $tools = ['search_contacts', 'get_contact_history', ...$tools];
        }

        if ($this->containsAny($normalized, ['prosp']) && $this->containsAny($normalized, ['non acquis', 'non conclus', 'non sono riuscit', 'non riuscit', 'pers', 'fallit', 'concludere'])) {
            // Non sostituire gli intenti già rilevati: una richiesta analitica
            // può richiedere contemporaneamente prospect, attività e storico.
            $tools = [...$tools, 'get_prospect_outcomes'];
            $this->addWhen($tools, $normalized, ['priorit', 'urgente', 'scadut', 'attivit', 'follow-up', 'follow up', 'prossim'], ['get_due_activities']);
            $this->addWhen($tools, $normalized, ['motivo', 'perch', 'storico', 'note', 'timeline', 'analizz'], ['search_contacts', 'get_contact_history']);
        } elseif ($this->containsAny($normalized, ['miglior client', 'cliente migliore', 'top client', 'clienti migliori', 'cliente piu importante', 'cliente piu redditizio'])) {
            $tools = [...$tools, 'get_client_rankings'];
        } elseif ($this->containsAny($normalized, ['client', 'prosp', 'contatt', 'acquisit', 'convertit'])) {
            // Quando è nominata una persona, la risoluzione del contatto viene
            // prima di agenda e pratiche: evita conclusioni premature.
            $tools = ['search_contacts', 'get_contact_history', ...$tools];
        }

        if ($advisory && $this->containsAny($normalized, ['client', 'prosp', 'contatt', 'persona', 'parlami', ' su ', ' per ', ' con '])) {
            $tools = ['search_contacts', 'get_contact_history', 'get_contact_consulting_dossier', ...$tools];
        }

        $this->addWhen($tools, $normalized, ['panoramica', 'riepilogo crm', 'situazione generale', 'quanti client', 'quanti prospect'], ['get_crm_overview']);
        if ($actionRequest) {
            $tools[] = 'propose_crm_action';
        }
        $tools = array_values(array_unique($tools));

        if ($tools === []) {
            $tools = ['search_contacts', 'get_contact_history', 'search_companies', 'get_company_history', 'get_goal_progress', 'get_due_activities', 'get_practices', 'get_expiring_documents', 'get_crm_overview', 'get_client_rankings', 'get_prospect_outcomes'];
        }

        return [
            'mode' => $advisory ? 'advisory' : 'crm',
            'tools' => $tools,
            'guidance' => $advisory ? 'Modalità consulenziale interna: ricostruisci il dossier completo e formula ipotesi operative verificabili, distinguendo fatti, inferenze e dati mancanti. Non dare consulenza finanziaria definitiva né inventare informazioni.' : 'Rispondi esclusivamente alla richiesta corrente. Usa solo gli strumenti pertinenti disponibili e non ripetere la risposta al turno precedente, salvo richiesta esplicita dell’utente.',
        ];
    }

    /** @param array<int, string> $tools @param array<int, string> $needles @param array<int, string> $matches */
    private function addWhen(array &$tools, string $message, array $needles, array $matches): void
    {
        if ($this->containsAny($message, $needles)) {
            $tools = [...$tools, ...$matches];
        }
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $message, array $needles): bool
    {
        return Str::contains($message, $needles);
    }

    private function isConversational(string $message): bool
    {
        return preg_match('/^(ciao|salve|buongiorno|buonasera|hey|come stai|come va|chi sei|grazie|perfetto|ok|va bene)[.!? ]*$/', $message) === 1;
    }
}
