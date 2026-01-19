<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersDummySeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user1@example.com'],
            ['name' => '一般ユーザー1', 'password' => Hash::make('password123')]
        );

        User::updateOrCreate(
            ['email' => 'user2@example.com'],
            ['name' => '一般ユーザー2', 'password' => Hash::make('password123')]
        );
    }
}
