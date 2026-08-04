<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\PracticeType;

final class GoalSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $types = PracticeType::query()->active()->ordered()->get()->keyBy('slug');
        foreach ([
            ['title' => 'Nuovi piani di accumulo', 'type' => 'pac', 'target_quantity' => 5],
            ['title' => 'Consulenze previdenziali concluse', 'type' => 'gestione-separata', 'target_quantity' => 4],
        ] as $data) {
            Goal::query()->updateOrCreate(['title' => $data['title'], 'owner_id' => $owner->id], ['practice_type_id' => $types[$data['type']]->id, 'target_quantity' => $data['target_quantity'], 'starts_at' => today()->startOfMonth(), 'ends_at' => today()->endOfMonth(), 'status' => GoalStatus::Active]);
        }
    }
}
