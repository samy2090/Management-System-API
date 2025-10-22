<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a manager
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('manager'),
            'role' => UserRole::MANAGER,
        ]);

        // Create 3 regular users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER,
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER,
        ]);

        User::create([
            'name' => 'Bob Johnson',
            'email' => 'bob@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER,
        ]);
    }
}