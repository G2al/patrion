<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentOutcome;
use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasSinglePrincipal;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Appointment extends Model
{
    use HasFactory, HasSinglePrincipal;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $appointment): void {
            if ($appointment->ends_at->lessThanOrEqualTo($appointment->starts_at)) {
                throw new DomainException('The appointment end must be after its start.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'outcome' => AppointmentOutcome::class,
            'prospect_interested' => 'boolean',
            'should_convert_to_client' => 'boolean',
            'should_open_practice' => 'boolean',
            'follow_up_required' => 'boolean',
            'next_contact_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
