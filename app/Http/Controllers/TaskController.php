<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use App\Enums\TaskStatus;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;

class TaskController extends BaseController
{
    public function __construct(
        private TaskService $taskService
    ) {
        //
    }

    /**
     * Display a listing of tasks with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'assignee_user', 'due_date_from', 'due_date_to', 
                'overdue', 'unassigned', 'search'
            ]);
            
            $perPage = $request->get('per_page', 15);
            $user = $request->user();

            $tasks = $this->taskService->getAllTasks($user, $filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving tasks');
        }
    }

    /**
     * Store a newly created task
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Authorize using policy
            $this->authorize('create', \App\Models\Task::class);
            
            $task = $this->taskService->createTask($user, $request->validated());

            return $this->sendResponse(new TaskResource($task), 'Task created successfully', 201);
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating task');
        }
    }

    /**
     * Display the specified task
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $task = $this->taskService->getTaskById($user, $id);

            if (!$task) {
                return $this->sendNotFound('Task not found');
            }

            // Authorize using policy
            $this->authorize('view', $task);

            // Load dependencies and dependent tasks
            $task->load(['dependencies', 'dependentTasks']);

            $taskData = new TaskResource($task);
            $additionalData = [
                'dependencies' => $task->dependencies->map(function ($dep) {
                    return [
                        'id' => $dep->id,
                        'title' => $dep->title,
                        'status' => $dep->status->value,
                        'status_label' => $dep->status->label(),
                    ];
                }),
                'dependent_tasks' => $task->dependentTasks->map(function ($dep) {
                    return [
                        'id' => $dep->id,
                        'title' => $dep->title,
                        'status' => $dep->status->value,
                        'status_label' => $dep->status->label(),
                    ];
                }),
                'can_be_completed' => $task->canBeCompleted(),
                'has_incomplete_dependencies' => $task->hasIncompleteDependencies(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => array_merge($taskData->toArray($request), $additionalData)
            ]);
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving task');
        }
    }

    /**
     * Update the specified task
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $task = $this->taskService->updateTask($user, $id, $request->validated());

            return $this->sendResponse(new TaskResource($task), 'Task updated successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating task');
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $this->taskService->deleteTask($user, $id);

            return $this->sendResponse(null, 'Task deleted successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting task');
        }
    }

    /**
     * Update task status only
     */
    public function updateStatus(UpdateTaskStatusRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $status = TaskStatus::from($request->input('status'));
            $task = $this->taskService->updateTaskStatus($user, $id, $status);

            return $this->sendResponse(new TaskResource($task), 'Task status updated successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating task status');
        }
    }

    /**
     * Assign task to a user
     */
    public function assignToUser(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        try {
            $user = $request->user();
            $task = $this->taskService->assignTaskToUser($user, $id, $request->input('user_id'));

            return $this->sendResponse(new TaskResource($task), 'Task assigned successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'assigning task');
        }
    }

    /**
     * Unassign task from current user
     */
    public function unassign(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $task = $this->taskService->unassignTask($user, $id);

            return $this->sendResponse(new TaskResource($task), 'Task unassigned successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'unassigning task');
        }
    }

    /**
     * Get tasks by status
     */
    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $taskStatus = TaskStatus::from($status);
            $user = $request->user();
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getTasksByStatus($user, $taskStatus, $perPage);

            return response()->json([
                'success' => true,
                'message' => "Tasks with status '{$status}' retrieved successfully",
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\ValueError $e) {
            return $this->sendError('Invalid status value', null, 400);
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving tasks by status');
        }
    }

    /**
     * Get overdue tasks
     */
    public function getOverdue(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getOverdueTasks($user, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Overdue tasks retrieved successfully',
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving overdue tasks');
        }
    }

    /**
     * Get unassigned tasks (managers only)
     */
    public function getUnassigned(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getUnassignedTasks($user, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Unassigned tasks retrieved successfully',
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving unassigned tasks');
        }
    }

    /**
     * Search tasks
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        try {
            $user = $request->user();
            $query = $request->input('q');
            $filters = $request->only([
                'status', 'assignee_user', 'due_date_from', 'due_date_to'
            ]);
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->searchTasks($user, $query, $filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved successfully',
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'query' => $query
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'searching tasks');
        }
    }

    /**
     * Get task statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->taskService->getTaskStatistics($user);

            return $this->sendResponse($stats, 'Task statistics retrieved successfully');
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving statistics');
        }
    }

    /**
     * Get my tasks (current user)
     */
    public function getMyTasks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $filters = $request->only([
                'status', 'due_date_from', 'due_date_to', 'overdue', 'search'
            ]);
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getMyTasks($user, $filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Your tasks retrieved successfully',
                'data' => new TaskCollection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving your tasks');
        }
    }

    /**
     * Get my task statistics (current user)
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->taskService->getUserTaskStatistics($user);

            return $this->sendResponse($stats, 'Your task statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'retrieving your statistics');
        }
    }

    /**
     * Update task for regular users (status only for their assigned tasks)
     */
    public function updateUserTask(Request $request, int $id): JsonResponse
    {
        // Validate that only status can be updated
        $request->validate([
            'status' => 'required|string|in:pending,in_progress,completed,canceled'
        ]);

        try {
            $user = $request->user();
            $status = TaskStatus::from($request->input('status'));
            $task = $this->taskService->updateTaskStatus($user, $id, $status);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => new TaskResource($task)
            ]);
        } catch (UnauthorizedException $e) {
            return $this->sendForbidden($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating task status');
        }
    }
}