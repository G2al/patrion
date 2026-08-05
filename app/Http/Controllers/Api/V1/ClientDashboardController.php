<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ClientDashboardActivityResource;
use App\Http\Resources\NeglectedClientResource;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class ClientDashboardController extends ApiController
{
    public function __invoke(Request $request)
    {
        $months = (int) $request->query('months', 6);
        $limit = (int) $request->query('neglected_limit', 10);
        abort_unless(in_array($months, [3, 6, 12], true), 422, 'Il parametro months deve essere 3, 6 o 12.');
        abort_if($limit < 1 || $limit > 50, 422, 'Il parametro neglected_limit deve essere compreso tra 1 e 50.');
        $now = CarbonImmutable::now(config('app.timezone'));
        $contacts = Contact::query()->with(['assignedUser', 'appointments' => fn ($q) => $q->where('owner_id', $request->user()->id)->where('starts_at', '<=', $now), 'activities' => fn ($q) => $q->where('owner_id', $request->user()->id)->where('status', 'completed'), 'emails' => fn ($q) => $q->where('user_id', $request->user()->id), 'notes' => fn ($q) => $q->where('author_id', $request->user()->id), 'timelineEvents' => fn ($q) => $q->where(fn ($q) => $q->whereNull('author_id')->orWhere('author_id', $request->user()->id))])->get();
        $todayAppointments = Appointment::query()->with('contact')->where('owner_id', $request->user()->id)->whereBetween('starts_at', [$now->startOfDay(), $now->endOfDay()])->get()->map(fn (Appointment $item) => (new ClientDashboardActivityResource($item))->resolve());
        $todayActivities = Activity::query()->with('contact')->where('owner_id', $request->user()->id)->where(function ($q) use ($now): void {
            $q->whereBetween('scheduled_at', [$now->startOfDay(), $now->endOfDay()])->orWhereBetween('due_at', [$now->startOfDay(), $now->endOfDay()]);
        })->get()->map(fn (Activity $item) => (new ClientDashboardActivityResource($item))->resolve());
        $neglected = $contacts->filter(fn (Contact $contact): bool => in_array($contact->status?->value, ['client', 'prospect'], true))->map(fn (Contact $contact): Contact => $this->decorateInteraction($contact, $now))->filter(fn (Contact $contact): bool => $contact->days_without_interactions > 60)->sortByDesc(fn (Contact $contact) => $contact->last_interaction_at?->timestamp ?? 0)->take($limit)->values();

        return $this->ok(['stats' => ['total' => $contacts->count() + Company::query()->count(), 'clients' => $contacts->where('status', 'client')->count(), 'prospects' => $contacts->where('status', 'prospect')->count(), 'companies' => Company::query()->count()], 'monthly_growth' => $this->growth($months, $now), 'today_activities' => $todayAppointments->concat($todayActivities)->sortBy('scheduled_at')->values()->all(), 'neglected_clients' => NeglectedClientResource::collection($neglected)->resolve()]);
    }

    private function growth(int $months, CarbonImmutable $now): array
    {
        $from = $now->startOfMonth()->subMonths($months - 1);
        $contacts = Contact::query()->where('created_at', '>=', $from)->get()->groupBy(fn (Contact $item) => $item->created_at->format('Y-m'));
        $companies = Company::query()->where('created_at', '>=', $from)->get()->groupBy(fn ($item) => $item->created_at->format('Y-m'));

        return collect(range(0, $months - 1))->map(function (int $offset) use ($from, $contacts, $companies): array {
            $date = $from->addMonths($offset);
            $key = $date->format('Y-m');
            $rows = $contacts->get($key, collect());
            $businesses = $companies->get($key, collect());

            return ['month' => $key, 'label' => $date->translatedFormat('M'), 'count' => $rows->count() + $businesses->count(), 'clients' => $rows->where('status', 'client')->count(), 'prospects' => $rows->where('status', 'prospect')->count(), 'companies' => $businesses->count()];
        })->all();
    }

    private function decorateInteraction(Contact $contact, CarbonImmutable $now): Contact
    {
        $events = collect();
        if ($contact->last_contact_at) {
            $events->push(['at' => $contact->last_contact_at, 'type' => 'contact']);
        }
        foreach ($contact->appointments as $item) {
            $events->push(['at' => $item->starts_at, 'type' => 'appointment']);
        }
        foreach ($contact->activities as $item) {
            $events->push(['at' => $item->completed_at ?? $item->updated_at, 'type' => 'activity']);
        }
        foreach ($contact->emails as $item) {
            $events->push(['at' => $item->sent_at ?? $item->received_at ?? $item->created_at, 'type' => 'email']);
        }
        foreach ($contact->notes as $item) {
            $events->push(['at' => $item->created_at, 'type' => 'note']);
        }
        foreach ($contact->timelineEvents as $item) {
            $events->push(['at' => $item->occurred_at, 'type' => 'timeline']);
        }
        $latest = $events->filter(fn (array $event): bool => $event['at'] !== null && $event['at']->lessThanOrEqualTo($now))->sortByDesc(fn (array $event) => $event['at']->timestamp)->first();
        $contact->last_interaction_at = $latest['at'] ?? null;
        $contact->last_interaction_type = $latest['type'] ?? null;
        $base = $latest['at'] ?? $contact->first_contact_date ?? $contact->created_at;
        $contact->days_without_interactions = $base ? (int) floor(abs($now->getTimestamp() - $base->getTimestamp()) / 86400) : 99999;

        return $contact;
    }
}
