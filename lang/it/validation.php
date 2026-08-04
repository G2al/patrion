<?php

declare(strict_types=1);

return [
    'required' => 'Il campo :attribute è obbligatorio.',
    'required_without' => 'Il campo :attribute è obbligatorio quando :values non è presente.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'url' => 'Il campo :attribute deve essere un URL valido.',
    'unique' => 'Il valore del campo :attribute è già presente.',
    'date' => 'Il campo :attribute deve contenere una data valida.',
    'after' => 'Il campo :attribute deve essere successivo a :date.',
    'after_or_equal' => 'Il campo :attribute deve essere successivo o uguale a :date.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'min' => ['numeric' => 'Il campo :attribute deve essere almeno :min.', 'string' => 'Il campo :attribute deve contenere almeno :min caratteri.'],
    'max' => ['numeric' => 'Il campo :attribute non può superare :max.', 'string' => 'Il campo :attribute non può superare :max caratteri.'],
    'attributes' => [
        'first_name' => 'nome', 'last_name' => 'cognome', 'name' => 'nome', 'title' => 'titolo',
        'email' => 'email', 'tax_code' => 'codice fiscale', 'vat_number' => 'partita IVA',
        'starts_at' => 'data iniziale', 'ends_at' => 'data finale', 'due_at' => 'scadenza',
        'contact_id' => 'contatto', 'company_id' => 'azienda', 'practice_type_id' => 'tipologia di pratica',
        'target_quantity' => 'quantità target', 'file_path' => 'file',
    ],
];
