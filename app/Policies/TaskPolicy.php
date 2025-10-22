<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Enums\UserRole;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        // Both managers and users can view tasks (filtered by their role)
        return true;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        // Managers can view all tasks
        if ($user->role === UserRole::MANAGER) {
            return true;
        }

        // Users can only view tasks assigned to them
        return $user->role === UserRole::USER && $task->assignee_user === $user->id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        // Only managers can create tasks
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        // Managers can update all tasks
        if ($user->role === UserRole::MANAGER) {
            return true;
        }

        // Users can only update tasks assigned to them
        return $user->role === UserRole::USER && $task->assignee_user === $user->id;
    }

    /**
     * Determine whether the user can update only the status of the task.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        // Managers can update status of all tasks
        if ($user->role === UserRole::MANAGER) {
            return true;
        }

        // Users can only update status of tasks assigned to them
        return $user->role === UserRole::USER && $task->assignee_user === $user->id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Only managers can delete tasks
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can assign tasks to other users.
     */
    public function assign(User $user): bool
    {
        // Only managers can assign tasks
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can view unassigned tasks.
     */
    public function viewUnassigned(User $user): bool
    {
        // Only managers can view unassigned tasks
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can view all task statistics.
     */
    public function viewAllStatistics(User $user): bool
    {
        // Only managers can view all task statistics
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can perform full CRUD operations.
     */
    public function manageAll(User $user): bool
    {
        // Only managers can perform full CRUD operations
        return $user->role === UserRole::MANAGER;
    }
}