<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PracticeTypeSeeder::class,
            ContactSeeder::class,
            CompanySeeder::class,
            CompanyContactSeeder::class,
            PracticeSeeder::class,
            AppointmentSeeder::class,
            ActivitySeeder::class,
            GoalSeeder::class,
            DocumentSeeder::class,
            NoteSeeder::class,
            TimelineEventSeeder::class,
            ContactProfessionalSeeder::class,
            ContactGoalSeeder::class,
            EmailSeeder::class,
        ]);
    }
}
