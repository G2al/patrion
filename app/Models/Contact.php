<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactStatus;
use App\Enums\Priority;
use App\Enums\ProspectSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'identity_document_expires_at' => 'date',
            'status' => ContactStatus::class,
            'first_contact_date' => 'date',
            'source' => ProspectSource::class,
            'priority' => Priority::class,
            'potential_value' => 'decimal:2',
            'managed_assets' => 'decimal:2',
            'last_contact_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'interests' => 'array',
            'personal_goals' => 'array',
            'hobbies' => 'array',
            'tags' => 'array',
            'relationship_score' => 'integer',
            'birthdays' => 'array',
            'anniversaries' => 'array',
        ];
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('status', ContactStatus::Client);
    }

    public function scopeProspects(Builder $query): Builder
    {
        return $query->where('status', ContactStatus::Prospect);
    }

    public function scopeFollowUpDue(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now());
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_contact_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_contact_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot('role')->withTimestamps();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(ContactProfessional::class);
    }

    public function clientGoals(): HasMany
    {
        return $this->hasMany(ContactGoal::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
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
