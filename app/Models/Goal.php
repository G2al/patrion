<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalStatus;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $goal): void {
            if ($goal->target_quantity < 1) {
                throw new DomainException('The target quantity must be at least one.');
            }

            if ($goal->ends_at->lessThan($goal->starts_at)) {
                throw new DomainException('The goal end date cannot precede its start date.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => GoalStatus::class,
        ];
    }

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getCurrentQuantityAttribute(): int
    {
        return Practice::query()
            ->completed()
            ->where('practice_type_id', $this->practice_type_id)
            ->whereBetween('completed_at', [$this->starts_at, $this->ends_at])
            ->count();
    }

    public function getProgressPercentageAttribute(): float
    {
        return min(100, round(($this->current_quantity / $this->target_quantity) * 100, 2));
    }
}
