<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\UnauthorizedException;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Get all tasks with role-based filtering
     */
    public function getAllTasks(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Users can only see tasks assigned to them
        if ($user->role === UserRole::USER) {
            return $this->taskRepository->getTasksByUser($user->id, $filters, $perPage);
        }

        // Managers can see all tasks
        return $this->taskRepository->getAll($filters, $perPage);
    }

    /**
     * Get task by ID with permission check
     */
    public function getTaskById(User $user, int $id): ?Task
    {
        $task = $this->taskRepository->findById($id);

        if (!$task) {
            return null;
        }

        // Users can only view tasks assigned to them
        if ($user->role === UserRole::USER && !$task->isAssignedTo($user->id)) {
            throw new UnauthorizedException('You can only view tasks assigned to you.');
        }

        return $task;
    }

    /**
     * Create a new task (managers only)
     */
    public function createTask(User $user, array $data): Task
    {
        $this->ensureIsManager($user);

        // Parse due_date if provided
        if (!empty($data['due_date'])) {
            $data['due_date'] = \Carbon\Carbon::parse($data['due_date']);
        }

        return $this->taskRepository->create($data);
    }

    /**
     * Update task with role-based permissions
     */
    public function updateTask(User $user, int $id, array $data): Task
    {
        $task = $this->getTaskById($user, $id);

        if (!$task) {
            throw new \Exception('Task not found.');
        }

        // Users can only update status of tasks assigned to them
        if ($user->role === UserRole::USER) {
            if (!$task->isAssignedTo($user->id)) {
                throw new UnauthorizedException('You can only update tasks assigned to you.');
            }

            // Users can only update status
            $allowedFields = ['status'];
            $data = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($data)) {
                throw new \Exception('You can only update the status field.');
            }
        }

        // Parse due_date if provided
        if (!empty($data['due_date'])) {
            $data['due_date'] = \Carbon\Carbon::parse($data['due_date']);
        }

        return $this->taskRepository->update($task, $data);
    }

    /**
     * Update task status only
     */
    public function updateTaskStatus(User $user, int $id, TaskStatus $status): Task
    {
        $task = $this->getTaskById($user, $id);

        if (!$task) {
            throw new \Exception('Task not found.');
        }

        // Users can only update status of tasks assigned to them
        if ($user->role === UserRole::USER && !$task->isAssignedTo($user->id)) {
            throw new UnauthorizedException('You can only update status of tasks assigned to you.');
        }

        // Check dependencies before marking as completed
        if ($status === TaskStatus::COMPLETED && $task->hasIncompleteDependencies()) {
            $incompleteDeps = $task->getIncompleteDependencies();
            $depTitles = $incompleteDeps->pluck('title')->toArray();
            throw new \Exception(
                'Cannot complete task. The following dependencies must be completed first: ' . 
                implode(', ', $depTitles)
            );
        }

        return $this->taskRepository->updateStatus($task, $status);
    }

    /**
     * Delete task (managers only)
     */
    public function deleteTask(User $user, int $id): bool
    {
        $this->ensureIsManager($user);

        $task = $this->taskRepository->findById($id);

        if (!$task) {
            throw new \Exception('Task not found.');
        }

        return $this->taskRepository->delete($task);
    }

    /**
     * Assign task to user (managers only)
     */
    public function assignTaskToUser(User $manager, int $taskId, int $userId): Task
    {
        $this->ensureIsManager($manager);

        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new \Exception('Task not found.');
        }

        return $this->taskRepository->assignToUser($task, $userId);
    }

    /**
     * Unassign task (managers only)
     */
    public function unassignTask(User $manager, int $taskId): Task
    {
        $this->ensureIsManager($manager);

        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new \Exception('Task not found.');
        }

        return $this->taskRepository->unassignTask($task);
    }

    /**
     * Get tasks by status
     */
    public function getTasksByStatus(User $user, TaskStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        // Users can only see their own tasks
        if ($user->role === UserRole::USER) {
            $filters = ['status' => $status];
            return $this->taskRepository->getTasksByUser($user->id, $filters, $perPage);
        }

        return $this->taskRepository->getTasksByStatus($status, $perPage);
    }

    /**
     * Get tasks within date range
     */
    public function getTasksByDateRange(User $user, string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        // Users can only see their own tasks
        if ($user->role === UserRole::USER) {
            $filters = [
                'due_date_from' => $startDate,
                'due_date_to' => $endDate
            ];
            return $this->taskRepository->getTasksByUser($user->id, $filters, $perPage);
        }

        return $this->taskRepository->getTasksByDateRange($startDate, $endDate, $perPage);
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(User $user, int $perPage = 15): LengthAwarePaginator
    {
        // Users can only see their own overdue tasks
        if ($user->role === UserRole::USER) {
            $filters = ['overdue' => true];
            return $this->taskRepository->getTasksByUser($user->id, $filters, $perPage);
        }

        return $this->taskRepository->getOverdueTasks($perPage);
    }

    /**
     * Get unassigned tasks (managers only)
     */
    public function getUnassignedTasks(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $this->ensureIsManager($user);

        return $this->taskRepository->getUnassignedTasks($perPage);
    }

    /**
     * Search tasks
     */
    public function searchTasks(User $user, string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Users can only search their own tasks
        if ($user->role === UserRole::USER) {
            $filters['assignee_user'] = $user->id;
        }

        return $this->taskRepository->searchTasks($query, $filters, $perPage);
    }

    /**
     * Get task statistics (managers only)
     */
    public function getTaskStatistics(User $user): array
    {
        $this->ensureIsManager($user);

        return $this->taskRepository->getTasksCountByStatus();
    }

    /**
     * Get user's task statistics
     */
    public function getUserTaskStatistics(User $user): array
    {
        $stats = [];
        foreach (TaskStatus::cases() as $status) {
            $filters = ['status' => $status];
            $stats[$status->value] = $this->taskRepository->getTasksByUser($user->id, $filters, 1)->total();
        }

        return $stats;
    }

    /**
     * Ensure user is a manager
     */
    private function ensureIsManager(User $user): void
    {
        if ($user->role !== UserRole::MANAGER) {
            throw new UnauthorizedException('Only managers can perform this action.');
        }
    }

    /**
     * Check if user can update task
     */
    public function canUpdateTask(User $user, Task $task): bool
    {
        if ($user->role === UserRole::MANAGER) {
            return true;
        }

        return $user->role === UserRole::USER && $task->isAssignedTo($user->id);
    }

    /**
     * Check if user can view task
     */
    public function canViewTask(User $user, Task $task): bool
    {
        if ($user->role === UserRole::MANAGER) {
            return true;
        }

        return $user->role === UserRole::USER && $task->isAssignedTo($user->id);
    }

    /**
     * Get tasks assigned to current user
     */
    public function getMyTasks(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksByUser($user->id, $filters, $perPage);
    }
}