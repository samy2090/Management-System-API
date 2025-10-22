<?php

namespace App\Observers;

use App\Models\TaskDependency;
use App\Services\TaskDependencyService;
use InvalidArgumentException;

class TaskDependencyObserver
{
    public function __construct(
        private TaskDependencyService $taskDependencyService
    ) {}

    /**
     * Handle the TaskDependency "creating" event.
     */
    public function creating(TaskDependency $taskDependency): void
    {
        // Validate that the dependency doesn't create a circular dependency
        if ($this->taskDependencyService->wouldCreateCircularDependency(
            $taskDependency->task_id, 
            $taskDependency->depends_on_task_id
        )) {
            throw new InvalidArgumentException('Cannot create dependency: would create circular dependency');
        }
        
        // Prevent self-dependency (though the service should catch this too)
        if ($taskDependency->task_id === $taskDependency->depends_on_task_id) {
            throw new InvalidArgumentException('A task cannot depend on itself');
        }
    }
    
    /**
     * Handle the TaskDependency "updating" event.
     */
    public function updating(TaskDependency $taskDependency): void
    {
        // Check if the update would create a circular dependency
        if ($taskDependency->isDirty(['task_id', 'depends_on_task_id'])) {
            if ($this->taskDependencyService->wouldCreateCircularDependency(
                $taskDependency->task_id, 
                $taskDependency->depends_on_task_id
            )) {
                throw new InvalidArgumentException('Cannot update dependency: would create circular dependency');
            }
        }
        
        // Prevent self-dependency
        if ($taskDependency->task_id === $taskDependency->depends_on_task_id) {
            throw new InvalidArgumentException('A task cannot depend on itself');
        }
    }
}