<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Services\TimelineRecorder;
use Illuminate\Support\Facades\DB;

class ConvertProspectToClient
{
    public function __construct(private readonly TimelineRecorder $timeline) {}

    public function handle(Contact $contact, ?int $authorId = null): Contact
    {
        return DB::transaction(function () use ($contact, $authorId): Contact {
            $contact->refresh();

            if ($contact->status === ContactStatus::Client) {
                return $contact;
            }

            $contact->update(['status' => ContactStatus::Client]);
            $this->timeline->record(
                $contact,
                'prospect_converted',
                'Prospect convertito in cliente',
                authorId: $authorId,
            );

            return $contact->refresh();
        });
    }
}
