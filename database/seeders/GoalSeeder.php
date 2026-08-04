<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\PracticeType;

class GoalSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $types = PracticeType::query()->active()->ordered()->take(6)->get();

        foreach ($types as $index => $type) {
            $title = "DEMO Obiettivo {$type->name}";
            if (! Goal::query()->where('title', $title)->exists()) {
                Goal::factory()->for($type, 'practiceType')->for($owner, 'owner')->create([
                    'title' => $title, 'description' => "Completare le pratiche {$type->name} nel periodo corrente.",
                    'target_quantity' => $type->slug === 'pac' ? 10 : 5 + $index,
                    'starts_at' => today()->startOfMonth(), 'ends_at' => $index < 4 ? today()->endOfMonth() : today()->addMonths(2)->endOfMonth(),
                    'status' => GoalStatus::Active,
                ]);
            }
        }
    }
}
