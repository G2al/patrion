<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Practice;

final class NoteSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        $practices = Practice::query()->get()->keyBy('internal_number');
        $notes = [
            ['contact' => 'luigi.iommelli@example.test', 'title' => 'Preferenza orario', 'content' => 'Luigi preferisce vedersi la mattina perché è più disponibile e concentrato.', 'important' => true],
            ['contact' => 'alessandra.costantini@example.test', 'title' => 'Documenti da raccogliere', 'content' => 'Prima del prossimo incontro servono documento di identità e situazione previdenziale.', 'important' => true],
            ['contact' => 'davide.moretti@example.test', 'title' => 'Obiezione sui costi', 'content' => 'Il prospect vuole confrontare i costi prima di procedere con una proposta.', 'important' => false],
            ['practice' => 'PR-2026-002', 'title' => 'Prossimo passo pratica', 'content' => 'Preparare la proposta di protezione e condividerla dopo la verifica documentale.', 'important' => false],
        ];

        foreach ($notes as $data) {
            $subject = isset($data['contact']) ? $contacts[$data['contact']] : $practices[$data['practice']];
            $subject->notes()->updateOrCreate(['title' => $data['title']], ['content' => $data['content'], 'is_important' => $data['important'], 'author_id' => $owner->id]);
        }
    }
}
