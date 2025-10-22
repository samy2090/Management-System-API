<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDependencyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| API Version 1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    
    // Task management routes with role-based access control
    Route::prefix('tasks')->group(function () {
        
        // Routes accessible to all authenticated users (with filtering based on role)
        Route::get('/', [TaskController::class, 'index']); 
        Route::get('/search/query', [TaskController::class, 'search']); 
        Route::get('/status/{status}', [TaskController::class, 'getByStatus']); 
        Route::get('/overdue/list', [TaskController::class, 'getOverdue']); 
        
        // Routes that require task access validation (users can only access their assigned tasks)
        Route::middleware('task.access')->group(function () {
            Route::get('/{id}', [TaskController::class, 'show']); 
            Route::patch('/{id}/status', [TaskController::class, 'updateStatus']); 
            
            // Task dependency routes (accessible to users for viewing their task dependencies)
            Route::get('/{task}/dependencies', [TaskDependencyController::class, 'index']); 
            Route::get('/{task}/dependent-tasks', [TaskDependencyController::class, 'dependentTasks']); 
        });
        
        // Manager-only routes
        Route::middleware('manager')->group(function () {
            Route::post('/', [TaskController::class, 'store']); 
            Route::put('/{id}', [TaskController::class, 'update']); 
            Route::patch('/{id}', [TaskController::class, 'update']);  
            Route::delete('/{id}', [TaskController::class, 'destroy']); 
            Route::post('/{id}/assign', [TaskController::class, 'assignToUser']); // Assign task
            Route::post('/{id}/unassign', [TaskController::class, 'unassign']); // Unassign task
            Route::get('/unassigned/list', [TaskController::class, 'getUnassigned']); // View unassigned tasks
            Route::get('/statistics/all', [TaskController::class, 'getStatistics']); // View all statistics
            
            // Task dependency management routes (managers only)
            Route::post('/{task}/dependencies', [TaskDependencyController::class, 'store']); // Add task dependency
            Route::post('/{task}/dependencies/multiple', [TaskDependencyController::class, 'storeMultiple']); // Add multiple task dependencies
            Route::delete('/{task}/dependencies/{dependsOnTaskId}', [TaskDependencyController::class, 'destroy']); // Remove task dependency
        });
        
        // Special route for users to update their assigned tasks (limited fields)
        Route::middleware('task.access')->group(function () {
            Route::patch('/{id}/update', [TaskController::class, 'updateUserTask']); // Users update their tasks (status only)
        });
    });
    
        // User-specific task routes (accessible to all users for their own tasks)
        Route::prefix('my-tasks')->group(function () {
            Route::get('/', [TaskController::class, 'getMyTasks']); // Get my tasks
            Route::get('/statistics', [TaskController::class, 'getMyStatistics']); // Get my statistics
        });
    });
});
