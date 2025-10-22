<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskDependencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tasks
        $tasks = Task::all();
        
        if ($tasks->count() < 2) {
            $this->command->warn('Not enough tasks found. Please run TaskSeeder first.');
            return;
        }

        // Convert to array for easier access by title
        $tasksByTitle = $tasks->keyBy('title');

        // Define logical dependencies based on realistic project workflow
        $dependencies = [
            // User Authentication depends on Database Setup
            'Implement User Authentication' => ['Setup Project Database'],
            
            // API Documentation depends on Authentication being implemented
            'Design API Documentation' => ['Implement User Authentication'],
            
            // Unit Tests depend on Authentication and Database
            'Write Unit Tests' => ['Setup Project Database', 'Implement User Authentication'],
            
            // CI/CD Pipeline depends on Unit Tests being written
            'Setup CI/CD Pipeline' => ['Write Unit Tests'],
            
            // Database Optimization can only happen after database is setup and tests are written
            'Optimize Database Queries' => ['Setup Project Database', 'Write Unit Tests'],
            
            // Mobile App Integration depends on API Documentation and Authentication
            'Mobile App Integration' => ['Design API Documentation', 'Implement User Authentication'],
            
            // Performance Monitoring depends on database being optimized
            'Performance Monitoring' => ['Optimize Database Queries'],
            
            // UI Redesign depends on Authentication and Performance Monitoring
            'User Interface Redesign' => ['Implement User Authentication', 'Performance Monitoring'],
        ];

        $createdDependencies = [];
        $skippedDependencies = [];

        foreach ($dependencies as $taskTitle => $dependsOnTitles) {
            // Find the main task
            $task = $tasksByTitle->get($taskTitle);
            if (!$task) {
                $skippedDependencies[] = "Task '{$taskTitle}' not found";
                continue;
            }

            foreach ($dependsOnTitles as $dependsOnTitle) {
                // Find the dependency task
                $dependsOnTask = $tasksByTitle->get($dependsOnTitle);
                if (!$dependsOnTask) {
                    $skippedDependencies[] = "Dependency task '{$dependsOnTitle}' not found";
                    continue;
                }

                // Check if dependency already exists
                $existingDependency = TaskDependency::where('task_id', $task->id)
                    ->where('depends_on_task_id', $dependsOnTask->id)
                    ->first();

                if ($existingDependency) {
                    $skippedDependencies[] = "Dependency '{$taskTitle}' -> '{$dependsOnTitle}' already exists";
                    continue;
                }

                // Create the dependency
                $dependency = TaskDependency::create([
                    'task_id' => $task->id,
                    'depends_on_task_id' => $dependsOnTask->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $createdDependencies[] = [
                    'task' => $taskTitle,
                    'depends_on' => $dependsOnTitle,
                    'task_id' => $task->id,
                    'depends_on_task_id' => $dependsOnTask->id,
                ];
            }
        }

        // Output results
        $this->command->info('Task Dependencies seeded successfully!');
        $this->command->info('Created ' . count($createdDependencies) . ' task dependencies.');
        
        if (!empty($createdDependencies)) {
            $this->command->info('Dependencies created:');
            foreach ($createdDependencies as $dep) {
                $this->command->line("  • '{$dep['task']}' depends on '{$dep['depends_on']}'");
            }
        }

        if (!empty($skippedDependencies)) {
            $this->command->warn('Skipped dependencies:');
            foreach ($skippedDependencies as $skip) {
                $this->command->line("  • {$skip}");
            }
        }

        // Show summary of task completion status
        $this->showCompletionSummary();
    }

    /**
     * Show which tasks can be completed based on their dependencies
     */
    private function showCompletionSummary(): void
    {
        $this->command->info('');
        $this->command->info('Task Completion Status Summary:');
        
        $tasks = Task::with(['dependencies'])->get();
        
        foreach ($tasks as $task) {
            $canComplete = $task->canBeCompleted();
            $dependencyCount = $task->dependencies->count();
            
            $status = $canComplete ? '✅' : '❌';
            $statusText = $canComplete ? 'Can be completed' : 'Cannot be completed';
            
            if ($dependencyCount > 0) {
                $incompleteDeps = $task->dependencies()->whereNotIn('status', ['completed'])->count();
                $statusText .= " ({$incompleteDeps}/{$dependencyCount} dependencies incomplete)";
            } else {
                $statusText .= " (no dependencies)";
            }
            
            $this->command->line("  {$status} {$task->title} [{$task->status->value}] - {$statusText}");
        }
    }
}
