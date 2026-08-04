<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $note): void {
            if (blank($note->noteable_type) || blank($note->noteable_id)) {
                throw new DomainException('A note must belong to a domain record.');
            }
        });
    }

    protected function casts(): array
    {
        return ['is_important' => 'boolean'];
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
