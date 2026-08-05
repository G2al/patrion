<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

final class DashboardController extends ApiController
{
    public function __invoke(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $nextAppointment = $user->appointments()->with(['contact', 'company'])->where('starts_at', '>=', now())->orderBy('starts_at')->first();

        return $this->ok([
            'generated_at' => now()->toIso8601String(),
            'stats' => [
                'clients' => Contact::query()->clients()->count(),
                'prospects' => Contact::query()->prospects()->count(),
                'prospects_follow_up_due' => Contact::query()->prospects()->followUpDue()->count(),
                'appointments_today' => $user->appointments()->whereDate('starts_at', today())->count(),
                'open_activities' => $user->activities()->open()->count(),
                'overdue_activities' => $user->activities()->due()->count(),
                'open_practices' => $user->practices()->whereNotIn('status', ['completed', 'unsuccessful', 'cancelled'])->count(),
                'documents_expiring_30_days' => $user->uploadedDocuments()->whereBetween('expires_at', [today(), today()->addDays(30)])->count(),
                'active_goals' => $user->goals()->where('status', 'active')->count(),
                'unread_emails' => $user->emails()->where('is_read', false)->count(),
            ],
            'next_appointment' => $nextAppointment,
            'priority_activities' => $user->activities()->with(['contact', 'company', 'practice'])->open()->orderByRaw('due_at IS NULL')->orderBy('due_at')->limit(5)->get(),
            'featured_practices' => $user->practices()->with(['contact', 'company', 'practiceType'])->whereNotIn('status', ['completed', 'cancelled'])->orderByDesc('priority')->orderBy('expected_at')->limit(5)->get(),
            'goals' => $user->goals()->with('practiceType')->where('status', 'active')->orderBy('ends_at')->limit(6)->get()->map(fn ($goal): array => [
                'id' => $goal->id,
                'name' => $goal->title,
                'status' => $goal->status?->value,
                'starts_at' => $goal->starts_at?->toDateString(),
                'ends_at' => $goal->ends_at?->toDateString(),
                'target_quantity' => $goal->target_quantity,
                'current_quantity' => $goal->current_quantity,
                'remaining_quantity' => max(0, $goal->target_quantity - $goal->current_quantity),
                'progress_percentage' => $goal->progress_percentage,
                'practice_type' => $goal->practiceType,
            ])->all(),
            'today_appointments' => $user->appointments()->with(['contact', 'company', 'practice'])->whereDate('starts_at', today())->orderBy('starts_at')->get(),
            'important_emails' => $user->emails()->with('contact')->orderByRaw('(is_read = 0 AND is_important = 1) DESC')->orderBy('is_read')->orderByDesc('is_important')->orderByDesc('received_at')->limit(3)->get(),
        ]);
    }
}
