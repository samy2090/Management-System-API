<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filtering and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find task by ID
     */
    public function findById(int $id): ?Task;

    /**
     * Create a new task
     */
    public function create(array $data): Task;

    /**
     * Update an existing task
     */
    public function update(Task $task, array $data): Task;

    /**
     * Delete a task
     */
    public function delete(Task $task): bool;

    /**
     * Get tasks assigned to a specific user
     */
    public function getTasksByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get tasks by status
     */
    public function getTasksByStatus(TaskStatus $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get tasks within a date range
     */
    public function getTasksByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get unassigned tasks
     */
    public function getUnassignedTasks(int $perPage = 15): LengthAwarePaginator;

    /**
     * Update task status only
     */
    public function updateStatus(Task $task, TaskStatus $status): Task;

    /**
     * Assign task to user
     */
    public function assignToUser(Task $task, int $userId): Task;

    /**
     * Unassign task from current user
     */
    public function unassignTask(Task $task): Task;

    /**
     * Get tasks count by status
     */
    public function getTasksCountByStatus(): array;

    /**
     * Search tasks by title or description
     */
    public function searchTasks(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator;
}