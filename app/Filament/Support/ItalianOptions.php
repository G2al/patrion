<?php

declare(strict_types=1);

namespace App\Filament\Support;

final class ItalianOptions
{
    public const PRIORITIES = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Bassa'];

    public const SOURCES = ['event' => 'Evento', 'referral' => 'Referral', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'client' => 'Cliente', 'cold_call' => 'Cold call', 'other' => 'Altro'];

    public const INTERESTS = ['investments' => 'Investimenti', 'pension' => 'Previdenza', 'protection' => 'Protezione', 'company' => 'Azienda', 'other' => 'Altro'];

    public const PERSONAL_GOALS = ['retirement' => 'Pensione', 'savings' => 'Accumulo', 'protection' => 'Protezione', 'children' => 'Figli', 'home' => 'Casa', 'income' => 'Reddito', 'succession' => 'Passaggio generazionale', 'company' => 'Azienda', 'other' => 'Altro'];

    public const PRACTICE_STATUSES = ['draft' => 'Bozza', 'in_progress' => 'In lavorazione', 'waiting' => 'In attesa', 'completed' => 'Completata', 'unsuccessful' => 'Non conclusa', 'cancelled' => 'Annullata'];

    public const APPOINTMENT_STATUSES = ['scheduled' => 'Programmato', 'completed' => 'Completato', 'cancelled' => 'Annullato', 'no_show' => 'Cliente assente'];

    public const APPOINTMENT_OUTCOMES = ['positive' => 'Positivo', 'negative' => 'Negativo', 'postponed' => 'Rinviato', 'to_follow_up' => 'Da approfondire'];

    public const APPOINTMENT_MODES = ['in_person' => 'In presenza', 'phone' => 'Telefonata', 'video_call' => 'Videochiamata', 'other' => 'Altro'];

    public const NEGATIVE_REASONS = ['not_interested' => 'Non interessato', 'unsuitable' => 'Proposta non adatta', 'bad_timing' => 'Tempistica non favorevole', 'insufficient_funds' => 'Mancanza di disponibilità economica', 'other_solution' => 'Ha scelto un’altra soluzione', 'future_contact' => 'Da ricontattare in futuro', 'no_response' => 'Nessuna risposta', 'other' => 'Altro'];

    public const ACTIVITY_TYPES = ['phone_call' => 'Telefonata', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'document_request' => 'Richiesta documenti', 'follow_up' => 'Follow-up', 'meeting' => 'Incontro', 'practice_review' => 'Verifica pratica', 'reminder' => 'Promemoria', 'general' => 'Attività generica'];

    public const ACTIVITY_STATUSES = ['pending' => 'Da fare', 'in_progress' => 'In corso', 'completed' => 'Completata', 'postponed' => 'Rinviata', 'cancelled' => 'Annullata'];

    public const GOAL_STATUSES = ['active' => 'Attivo', 'achieved' => 'Raggiunto', 'expired' => 'Scaduto', 'cancelled' => 'Annullato'];

    public const DOCUMENT_STATUSES = ['valid' => 'Valido', 'expired' => 'Scaduto', 'archived' => 'Archiviato'];

    public const DOCUMENT_CATEGORIES = ['Documento di identità' => 'Documento di identità', 'Tessera sanitaria' => 'Tessera sanitaria', 'Contratto' => 'Contratto', 'Questionario' => 'Questionario', 'Estratto conto' => 'Estratto conto', 'Bilancio' => 'Bilancio', 'Visura' => 'Visura', 'Statuto' => 'Statuto', 'Altro' => 'Altro'];

    public const COMPANY_ROLES = ['administrator' => 'Amministratore', 'shareholder' => 'Socio', 'cfo' => 'CFO', 'accountant' => 'Commercialista', 'manager' => 'Responsabile', 'contact_person' => 'Referente', 'other' => 'Altro'];

    public static function badgeColor(?string $value): string
    {
        return match ($value) {
            'high', 'negative', 'expired', 'unsuccessful' => 'danger',
            'medium', 'waiting', 'postponed', 'to_follow_up' => 'warning',
            'client', 'completed', 'achieved', 'positive', 'valid' => 'success',
            'in_progress' => 'info',
            default => 'gray',
        };
    }
}
