<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'owner_id');
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class, 'owner_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'owner_id');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class, 'author_id');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }
}
