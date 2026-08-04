<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PracticeType;
use Illuminate\Database\Seeder;

class PracticeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $practiceTypes = [
            ['name' => 'Finanziamento', 'slug' => 'finanziamento', 'sort_order' => 10],
            ['name' => 'PAC', 'slug' => 'pac', 'sort_order' => 20],
            ['name' => 'Prestito', 'slug' => 'prestito', 'sort_order' => 30],
            ['name' => 'Gestione separata', 'slug' => 'gestione-separata', 'sort_order' => 40],
            ['name' => 'Patrimoniale', 'slug' => 'patrimoniale', 'sort_order' => 50],
            ['name' => 'Investimento classico', 'slug' => 'investimento-classico', 'sort_order' => 60],
        ];

        foreach ($practiceTypes as $practiceType) {
            PracticeType::query()->updateOrCreate(
                ['slug' => $practiceType['slug']],
                [...$practiceType, 'is_active' => true],
            );
        }
    }
}
