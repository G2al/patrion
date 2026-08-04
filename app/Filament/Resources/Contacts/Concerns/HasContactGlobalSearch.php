<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts\Concerns;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

trait HasContactGlobalSearch
{
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'whatsapp', 'tax_code'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Contact $record */
        return "{$record->first_name} {$record->last_name}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Contact $record */
        return [
            'Tipo' => $record->status === ContactStatus::Client ? 'Cliente' : 'Prospect',
            'Telefono' => $record->phone ?: '-',
            'Email' => $record->email ?: '-',
        ];
    }
}
