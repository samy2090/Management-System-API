<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Task;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaskAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login first.'
            ], 401);
        }

        // Managers have access to all tasks
        if ($user->role === UserRole::MANAGER) {
            return $next($request);
        }

        // For regular users, check if they have access to the specific task
        $taskId = $request->route('task') ?? $request->route('id');
        
        if (!$taskId) {
            return response()->json([
                'success' => false,
                'message' => 'Task ID is required.'
            ], 400);
        }

        $task = Task::find($taskId);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.'
            ], 404);
        }

        // Users can only access tasks assigned to them
        if ($user->role === UserRole::USER && $task->assignee_user !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only access tasks assigned to you.'
            ], 403);
        }

        return $next($request);
    }
}