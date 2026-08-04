<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        User::query()->forceCreate([
            'name' => 'admin',
            'email' => 'admin@patrion.it',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);
    }
}
