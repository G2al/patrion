<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeStatus;
use App\Enums\Priority;
use App\Models\Concerns\HasSinglePrincipal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Practice extends Model
{
    use HasFactory, HasSinglePrincipal;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $practice): void {
            if ($practice->status === PracticeStatus::Completed) {
                $practice->completed_at ??= today();
            } elseif ($practice->isDirty('status')) {
                $practice->completed_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PracticeStatus::class,
            'priority' => Priority::class,
            'opened_at' => 'date',
            'expected_at' => 'date',
            'completed_at' => 'date',
            'expected_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
        ];
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PracticeStatus::Completed);
    }

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
