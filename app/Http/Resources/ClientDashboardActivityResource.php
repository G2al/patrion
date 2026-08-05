<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClientDashboardActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAppointment = $this->resource instanceof Appointment;
        $contact = $this->contact;

        return ['id' => ($isAppointment ? 'appointment:' : 'activity:').$this->id, 'entity_type' => $isAppointment ? 'appointment' : 'activity', 'title' => $this->title, 'activity_type' => $isAppointment ? 'appointment' : $this->type?->value, 'scheduled_at' => ($isAppointment ? $this->starts_at : ($this->scheduled_at ?? $this->due_at))?->toIso8601String(), 'client' => $contact ? ['id' => "contact:{$contact->id}", 'name' => trim("{$contact->first_name} {$contact->last_name}"), 'client_type' => $contact->status?->value] : null, 'frontend_path' => $isAppointment ? "/appointments/{$this->id}" : "/activities/{$this->id}"];
    }
}
