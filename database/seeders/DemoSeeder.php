<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

abstract class DemoSeeder extends Seeder
{
    protected function owner(): User
    {
        return User::query()->sole();
    }
}
