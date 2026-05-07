<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@aken.id'],
            [
                'name' => 'Aken Admin',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
            ]
        );
    }
}
