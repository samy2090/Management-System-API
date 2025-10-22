<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskDependencyRequest;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Http\JsonResponse;

class TaskDependencyController extends Controller
{
    /**
     * Display a listing of task dependencies.
     */
    public function index(Task $task): JsonResponse
    {
        $dependencies = $task->dependencies()->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'dependencies' => $dependencies->map(function ($dependency) {
                    return [
                        'id' => $dependency->id,
                        'title' => $dependency->title,
                        'status' => $dependency->status->value,
                        'status_label' => $dependency->status->label(),
                    ];
                }),
                'can_be_completed' => $task->canBeCompleted(),
            ]
        ]);
    }

    /**
     * Store a newly created task dependency.
     */
    public function store(TaskDependencyRequest $request, Task $task): JsonResponse
    {
        $dependency = TaskDependency::create([
            'task_id' => $task->id,
            'depends_on_task_id' => $request->depends_on_task_id
        ]);

        $dependencyTask = Task::find($request->depends_on_task_id);

        return response()->json([
            'success' => true,
            'message' => 'Task dependency added successfully.',
            'data' => [
                'dependency' => [
                    'id' => $dependency->id,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'depends_on_task_id' => $dependencyTask->id,
                    'depends_on_task_title' => $dependencyTask->title,
                    'depends_on_task_status' => $dependencyTask->status->value,
                    'depends_on_task_status_label' => $dependencyTask->status->label(),
                ]
            ]
        ], 201);
    }

    /**
     * Store multiple task dependencies at once.
     */
    public function storeMultiple(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'depends_on_task_ids' => 'required|array|min:1',
            'depends_on_task_ids.*' => 'required|integer|exists:tasks,id|different:' . $task->id,
        ]);

        $dependencyIds = $request->depends_on_task_ids;
        $addedDependencies = [];
        $errors = [];

        foreach ($dependencyIds as $dependsOnTaskId) {
            // Check for circular dependency
            if (Task::wouldCreateCircularDependency($task->id, $dependsOnTaskId)) {
                $errors[] = "Task {$dependsOnTaskId} would create a circular dependency";
                continue;
            }

            // Check if dependency already exists
            $existingDependency = TaskDependency::where('task_id', $task->id)
                ->where('depends_on_task_id', $dependsOnTaskId)
                ->first();

            if ($existingDependency) {
                $errors[] = "Dependency on task {$dependsOnTaskId} already exists";
                continue;
            }

            // Create the dependency
            $dependency = TaskDependency::create([
                'task_id' => $task->id,
                'depends_on_task_id' => $dependsOnTaskId
            ]);

            $dependencyTask = Task::find($dependsOnTaskId);
            $addedDependencies[] = [
                'id' => $dependency->id,
                'depends_on_task_id' => $dependencyTask->id,
                'depends_on_task_title' => $dependencyTask->title,
                'depends_on_task_status' => $dependencyTask->status->value,
                'depends_on_task_status_label' => $dependencyTask->status->label(),
            ];
        }

        $response = [
            'success' => !empty($addedDependencies),
            'message' => count($addedDependencies) > 0 
                ? count($addedDependencies) . ' dependencies added successfully.' 
                : 'No dependencies were added.',
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'added_dependencies' => $addedDependencies,
            ]
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return response()->json($response, !empty($addedDependencies) ? 201 : 400);
    }

    /**
     * Remove the specified task dependency.
     */
    public function destroy(Task $task, int $dependsOnTaskId): JsonResponse
    {
        $dependency = TaskDependency::where('task_id', $task->id)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->first();

        if (!$dependency) {
            return response()->json([
                'success' => false,
                'message' => 'Dependency not found.'
            ], 404);
        }

        $dependency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task dependency removed successfully.'
        ]);
    }

    /**
     * Get all tasks that depend on the given task.
     */
    public function dependentTasks(Task $task): JsonResponse
    {
        $dependentTasks = $task->dependentTasks()->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'dependent_tasks' => $dependentTasks->map(function ($dependentTask) {
                    return [
                        'id' => $dependentTask->id,
                        'title' => $dependentTask->title,
                        'status' => $dependentTask->status->value,
                        'status_label' => $dependentTask->status->label(),
                    ];
                })
            ]
        ]);
    }
}
