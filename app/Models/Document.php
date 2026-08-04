<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expires_at' => 'date',
            'status' => DocumentStatus::class,
        ];
    }

    public function scopeExpiringBy(Builder $query, mixed $date): Builder
    {
        return $query->whereNotNull('expires_at')->whereDate('expires_at', '<=', $date);
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

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
