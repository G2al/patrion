<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Company extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:2',
            'liquidity' => 'decimal:2',
            'investments' => 'decimal:2',
            'financing' => 'decimal:2',
            'insurance' => 'decimal:2',
            'pension' => 'decimal:2',
            'opportunities' => 'array',
        ];
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withPivot('role')->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(TimelineEvent::class, 'subject');
    }
}
