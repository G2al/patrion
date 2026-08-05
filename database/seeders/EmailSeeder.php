<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Email;

final class EmailSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        $rows = [
            ['contact' => 'luigi.iommelli@example.test', 'sender_name' => 'Luigi Iommelli', 'sender_email' => 'luigi.iommelli@example.test', 'subject' => 'Disponibilità per il prossimo incontro', 'body' => 'Buongiorno, per me sarebbe comodo incontrarci in mattinata la prossima settimana.', 'direction' => 'incoming', 'is_read' => false, 'is_important' => true],
            ['contact' => 'giulia.bianchi@example.test', 'sender_name' => 'Patrion', 'sender_email' => 'admin@patrion.it', 'subject' => 'Riepilogo della consulenza', 'body' => 'Ti invio il riepilogo dei punti discussi durante il nostro incontro.', 'direction' => 'outgoing', 'is_read' => true, 'is_important' => false],
            ['contact' => 'marco.rinaldi@example.test', 'sender_name' => 'Marco Rinaldi', 'sender_email' => 'marco.rinaldi@example.test', 'subject' => 'Documenti integrativi', 'body' => 'Ho raccolto i documenti richiesti e li porterò al prossimo appuntamento.', 'direction' => 'incoming', 'is_read' => false, 'is_important' => true],
            ['contact' => 'alessandra.costantini@example.test', 'sender_name' => 'Patrion', 'sender_email' => 'admin@patrion.it', 'subject' => 'Follow-up previdenza', 'body' => 'Come concordato, ti ricontatto per verificare i prossimi passi.', 'direction' => 'outgoing', 'is_read' => true, 'is_important' => false],
            ['contact' => 'davide.moretti@example.test', 'sender_name' => 'Davide Moretti', 'sender_email' => 'davide.moretti@example.test', 'subject' => 'Richiesta informazioni costi', 'body' => 'Prima di fissare il prossimo incontro vorrei approfondire i costi.', 'direction' => 'incoming', 'is_read' => false, 'is_important' => false],
            ['contact' => 'luigi.iommelli@example.test', 'sender_name' => 'Patrion', 'sender_email' => 'admin@patrion.it', 'subject' => 'Conferma appuntamento', 'body' => 'Confermo il nostro incontro di martedì alle 10:00.', 'direction' => 'outgoing', 'is_read' => true, 'is_important' => true],
            ['contact' => 'giulia.bianchi@example.test', 'sender_name' => 'Giulia Bianchi', 'sender_email' => 'giulia.bianchi@example.test', 'subject' => 'Aggiornamento obiettivi', 'body' => 'Vorrei aggiornare gli obiettivi personali discussi.', 'direction' => 'incoming', 'is_read' => false, 'is_important' => false],
            ['contact' => 'marco.rinaldi@example.test', 'sender_name' => 'Patrion', 'sender_email' => 'admin@patrion.it', 'subject' => 'Documentazione pratica', 'body' => 'La pratica è stata aggiornata con i documenti ricevuti.', 'direction' => 'outgoing', 'is_read' => true, 'is_important' => false],
        ];
        foreach ($rows as $row) {
            $contact = $contacts[$row['contact']];
            $received = now()->subDays(random_int(1, 12));
            unset($row['contact']);
            Email::query()->updateOrCreate(['user_id' => $owner->id, 'subject' => $row['subject']], [...$row, 'contact_id' => $contact->id, 'recipient_email' => $row['direction'] === 'incoming' ? 'admin@patrion.it' : $contact->email, 'preview' => mb_substr($row['body'], 0, 180), 'received_at' => $received, 'sent_at' => $row['direction'] === 'outgoing' ? $received : null]);
        }
    }
}
