<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use DomainException;
use Illuminate\Database\Eloquent\Model;

trait HasSinglePrincipal
{
    public static function bootHasSinglePrincipal(): void
    {
        static::saving(function (Model $model): void {
            $principalCount = collect(['contact_id', 'company_id'])
                ->filter(fn (string $attribute): bool => filled($model->getAttribute($attribute)))
                ->count();

            if ($principalCount !== 1) {
                throw new DomainException('A record must belong to exactly one contact or company.');
            }
        });
    }
}
