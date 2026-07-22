<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);

        User::create([
            'name' => 'Coordinator',
            'email' => 'coordinator@example.com',
            'password' => 'password',
            'role' => UserRole::COORDINATOR,
        ]);

        User::create([
            'name' => 'Executor',
            'email' => 'executor@example.com',
            'password' => 'password',
            'role' => UserRole::EXECUTOR,
        ]);


        User::create([
            'name' => 'Transfer Executor',
            'email' => 'transfer-executor@example.com',
            'password' => 'password',
            'role' => UserRole::TRANSFER_EXECUTOR,
        ]);
    }
}
