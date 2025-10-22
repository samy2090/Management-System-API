<?php

namespace App\Repositories;

use App\Models\Task;
use App\Enums\TaskStatus;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filtering and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with('assignee');

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find task by ID
     */
    public function findById(int $id): ?Task
    {
        return Task::with('assignee')->find($id);
    }

    /**
     * Create a new task
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Update an existing task
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    /**
     * Delete a task
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Get tasks assigned to a specific user
     */
    public function getTasksByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with('assignee')->byAssignee($userId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get tasks by status
     */
    public function getTasksByStatus(TaskStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return Task::with('assignee')
            ->byStatus($status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get tasks within a date range
     */
    public function getTasksByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        $fromDate = Carbon::parse($startDate)->startOfDay();
        $toDate = Carbon::parse($endDate)->endOfDay();
        
        return Task::with('assignee')
            ->byDueDateRange($fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s'))
            ->orderBy('due_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(int $perPage = 15): LengthAwarePaginator
    {
        return Task::with('assignee')
            ->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELED])
            ->orderBy('due_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get unassigned tasks
     */
    public function getUnassignedTasks(int $perPage = 15): LengthAwarePaginator
    {
        return Task::with('assignee')
            ->unassigned()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Update task status only
     */
    public function updateStatus(Task $task, TaskStatus $status): Task
    {
        $task->update(['status' => $status]);
        return $task->fresh();
    }

    /**
     * Assign task to user
     */
    public function assignToUser(Task $task, int $userId): Task
    {
        $task->update(['assignee_user' => $userId]);
        return $task->fresh();
    }

    /**
     * Unassign task from current user
     */
    public function unassignTask(Task $task): Task
    {
        $task->update(['assignee_user' => null]);
        return $task->fresh();
    }

    /**
     * Get tasks count by status
     */
    public function getTasksCountByStatus(): array
    {
        $counts = [];
        foreach (TaskStatus::cases() as $status) {
            $counts[$status->value] = Task::byStatus($status)->count();
        }
        return $counts;
    }

    /**
     * Search tasks by title or description
     */
    public function searchTasks(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $taskQuery = Task::with('assignee')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });

        $this->applyFilters($taskQuery, $filters);

        return $taskQuery->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, array $filters): void
    {
        // Filter by status
        if (!empty($filters['status'])) {
            $status = is_string($filters['status']) 
                ? TaskStatus::from($filters['status']) 
                : $filters['status'];
            $query->byStatus($status);
        }

        // Filter by assignee
        if (!empty($filters['assignee_user'])) {
            $query->byAssignee($filters['assignee_user']);
        }

        // Filter by due date range
        if (!empty($filters['due_date_from']) && !empty($filters['due_date_to'])) {
            $fromDate = Carbon::parse($filters['due_date_from'])->startOfDay();
            $toDate = Carbon::parse($filters['due_date_to'])->endOfDay();
            $query->byDueDateRange($fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s'));
        } elseif (!empty($filters['due_date_from'])) {
            $fromDate = Carbon::parse($filters['due_date_from'])->startOfDay();
            $query->dueAfter($fromDate->format('Y-m-d H:i:s'));
        } elseif (!empty($filters['due_date_to'])) {
            $toDate = Carbon::parse($filters['due_date_to'])->endOfDay();
            $query->dueBefore($toDate->format('Y-m-d H:i:s'));
        }

        // Filter overdue tasks
        if (!empty($filters['overdue']) && $filters['overdue']) {
            $query->where('due_date', '<', now())
                  ->whereNotIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELED]);
        }

        // Filter unassigned tasks
        if (!empty($filters['unassigned']) && $filters['unassigned']) {
            $query->unassigned();
        }

        // Search in title and description
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }
    }
}