<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository interfaces to their implementations
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\TaskRepositoryInterface::class, \App\Repositories\TaskRepository::class);
        
        // Register services
        $this->app->singleton(\App\Services\TaskDependencyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\TaskDependency::observe(\App\Observers\TaskDependencyObserver::class);
    }
}
