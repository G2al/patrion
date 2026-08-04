<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $activity): void {
            if ($activity->status === ActivityStatus::Completed) {
                $activity->completed_at ??= now();
            } elseif ($activity->isDirty('status')) {
                $activity->completed_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
            'priority' => Priority::class,
            'status' => ActivityStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ActivityStatus::Pending, ActivityStatus::InProgress, ActivityStatus::Postponed]);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<=', now());
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
