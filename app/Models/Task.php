<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'assignee_user',
        'status',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'datetime',
        ];
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user');
    }

    /**
     * Scope to filter tasks by status
     */
    public function scopeByStatus($query, TaskStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter tasks by assignee
     */
    public function scopeByAssignee($query, int $userId)
    {
        return $query->where('assignee_user', $userId);
    }

    /**
     * Scope to filter tasks by due date range
     */
    public function scopeByDueDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter tasks due before a specific date
     */
    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    /**
     * Scope to filter tasks due after a specific date
     */
    public function scopeDueAfter($query, $date)
    {
        return $query->where('due_date', '>=', $date);
    }

    /**
     * Scope to get unassigned tasks
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assignee_user');
    }

    /**
     * Check if task is assigned to a specific user
     */
    public function isAssignedTo(int $userId): bool
    {
        return $this->assignee_user === $userId;
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->status->isCompleted();
    }

    /**
     * Get formatted due date for display
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        return $this->due_date?->format('Y-m-d H:i:s');
    }

    /**
     * Get tasks that this task depends on (prerequisites)
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
                    ->withTimestamps();
    }

    /**
     * Get tasks that depend on this task
     */
    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
                    ->withTimestamps();
    }

    /**
     * Check if task can be completed (all dependencies are completed)
     */
    public function canBeCompleted(): bool
    {
        return $this->dependencies()->whereNotIn('status', [TaskStatus::COMPLETED->value])->count() === 0;
    }

    /**
     * Check if task has any incomplete dependencies
     */
    public function hasIncompleteDependencies(): bool
    {
        return !$this->canBeCompleted();
    }

    /**
     * Get incomplete dependencies
     */
    public function getIncompleteDependencies()
    {
        return $this->dependencies()->whereNotIn('status', [TaskStatus::COMPLETED->value])->get();
    }
}