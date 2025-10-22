<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskDependency;

class TaskDependencyService
{
    /**
     * Check if adding a dependency would create a circular dependency
     * 
     * @param int $taskId The task that wants to depend on another task
     * @param int $dependsOnTaskId The task that $taskId wants to depend on
     * @return bool True if adding this dependency would create a circular dependency
     */
    public function wouldCreateCircularDependency(int $taskId, int $dependsOnTaskId): bool
    {
        // If we're trying to make a task depend on itself, that's circular
        if ($taskId === $dependsOnTaskId) {
            return true;
        }

        return $this->hasCircularDependency($taskId, $dependsOnTaskId, []);
    }

    /**
     * Recursively check for circular dependencies
     * 
     * @param int $originalTaskId The original task that wants to depend on something
     * @param int $currentTaskId The current task being checked in the dependency chain
     * @param array $visited Array to track visited tasks to detect cycles
     * @return bool
     */
    private function hasCircularDependency(int $originalTaskId, int $currentTaskId, array $visited): bool
    {
        // If we've already visited this task in our search, we found a cycle
        if (in_array($currentTaskId, $visited)) {
            return true;
        }

        // Add the current task to visited to track the path
        $visited[] = $currentTaskId;

        // Get all tasks that the current task depends on
        $dependencies = TaskDependency::where('task_id', $currentTaskId)
            ->pluck('depends_on_task_id');

        foreach ($dependencies as $dependsOnTaskId) {
            // If current task depends on the original task (directly or indirectly),
            // adding original task depends on current task would create a cycle
            if ($dependsOnTaskId == $originalTaskId || 
                $this->hasCircularDependency($originalTaskId, $dependsOnTaskId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new task dependency with validation
     * 
     * @param int $taskId
     * @param int $dependsOnTaskId
     * @return TaskDependency
     * @throws \InvalidArgumentException
     */
    public function createDependency(int $taskId, int $dependsOnTaskId): TaskDependency
    {
        if ($this->wouldCreateCircularDependency($taskId, $dependsOnTaskId)) {
            throw new \InvalidArgumentException('Cannot create dependency: would create circular dependency');
        }

        if ($taskId === $dependsOnTaskId) {
            throw new \InvalidArgumentException('A task cannot depend on itself');
        }

        // Check if dependency already exists
        $existing = TaskDependency::where('task_id', $taskId)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('Dependency already exists');
        }

        return TaskDependency::create([
            'task_id' => $taskId,
            'depends_on_task_id' => $dependsOnTaskId
        ]);
    }

    /**
     * Create multiple dependencies at once
     * 
     * @param int $taskId
     * @param array $dependsOnTaskIds
     * @return array ['created' => TaskDependency[], 'errors' => string[]]
     */
    public function createMultipleDependencies(int $taskId, array $dependsOnTaskIds): array
    {
        $created = [];
        $errors = [];

        foreach ($dependsOnTaskIds as $dependsOnTaskId) {
            try {
                $created[] = $this->createDependency($taskId, $dependsOnTaskId);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Task {$dependsOnTaskId}: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Remove a task dependency
     * 
     * @param int $taskId
     * @param int $dependsOnTaskId
     * @return bool
     */
    public function removeDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $dependency = TaskDependency::where('task_id', $taskId)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->first();

        if (!$dependency) {
            return false;
        }

        $dependency->delete();
        return true;
    }

    /**
     * Get all tasks that the given task depends on
     * 
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDependencies(int $taskId)
    {
        return Task::whereIn('id', 
            TaskDependency::where('task_id', $taskId)->pluck('depends_on_task_id')
        )->get();
    }

    /**
     * Get all tasks that depend on the given task
     * 
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDependentTasks(int $taskId)
    {
        return Task::whereIn('id', 
            TaskDependency::where('depends_on_task_id', $taskId)->pluck('task_id')
        )->get();
    }
}