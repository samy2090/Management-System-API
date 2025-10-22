<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\TaskStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users for task assignment
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $tasks = [
            [
                'title' => 'Setup Project Database',
                'description' => 'Create and configure the database schema for the new project including all necessary tables and relationships.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::COMPLETED->value,
                'due_date' => Carbon::now()->subDays(5),
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Implement User Authentication',
                'description' => 'Develop user login, registration, and password reset functionality using Laravel Sanctum.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::IN_PROGRESS->value,
                'due_date' => Carbon::now()->addDays(3),
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDay(),
            ],
            [
                'title' => 'Design API Documentation',
                'description' => 'Create comprehensive API documentation using Swagger/OpenAPI specifications.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::PENDING->value,
                'due_date' => Carbon::now()->addDays(7),
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Setup CI/CD Pipeline',
                'description' => 'Configure GitHub Actions for automated testing and deployment.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::PENDING->value,
                'due_date' => Carbon::now()->addDays(10),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Write Unit Tests',
                'description' => 'Develop comprehensive unit tests for all service classes and repositories.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::IN_PROGRESS->value,
                'due_date' => Carbon::now()->addDays(5),
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subHours(6),
            ],
            [
                'title' => 'Optimize Database Queries',
                'description' => 'Review and optimize slow database queries, add proper indexing.',
                'assignee_user' => null, // Unassigned task
                'status' => TaskStatus::PENDING->value,
                'due_date' => Carbon::now()->addDays(14),
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => 'Security Audit',
                'description' => 'Conduct a comprehensive security audit of the application and fix any vulnerabilities.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::CANCELED->value,
                'due_date' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(15),
                'updated_at' => Carbon::now()->subDays(8),
            ],
            [
                'title' => 'Mobile App Integration',
                'description' => 'Develop API endpoints for mobile application integration.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::PENDING->value,
                'due_date' => Carbon::now()->addDays(21),
                'created_at' => Carbon::now()->subDay(),
                'updated_at' => Carbon::now()->subDay(),
            ],
            [
                'title' => 'Performance Monitoring',
                'description' => 'Implement performance monitoring and logging system.',
                'assignee_user' => $users->random()->id,
                'status' => TaskStatus::COMPLETED->value,
                'due_date' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(12),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => 'User Interface Redesign',
                'description' => null, // Task without description
                'assignee_user' => null, // Unassigned task
                'status' => TaskStatus::PENDING->value,
                'due_date' => null, // Task without due date
                'created_at' => Carbon::now()->subHours(12),
                'updated_at' => Carbon::now()->subHours(12),
            ],
        ];

        DB::table('tasks')->insert($tasks);
        
        $this->command->info('Tasks seeded successfully!');
        $this->command->info('Created ' . count($tasks) . ' tasks with various statuses and assignments.');
    }
}
