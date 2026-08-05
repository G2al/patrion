<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NeglectedClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => "contact:{$this->id}", 'entity_type' => 'contact', 'client_type' => $this->status?->value, 'record_id' => $this->id, 'name' => trim("{$this->first_name} {$this->last_name}"), 'email' => $this->email, 'phone' => $this->phone, 'photo_url' => $this->photo_path ? "/api/v1/contacts/{$this->id}/photo" : null, 'priority' => $this->priority?->value, 'assigned_user' => $this->assignedUser, 'last_interaction_at' => $this->last_interaction_at?->toIso8601String(), 'last_interaction_type' => $this->last_interaction_type, 'days_without_interactions' => $this->days_without_interactions, 'frontend_path' => "/clients/contact:{$this->id}"];
    }
}
