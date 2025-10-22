<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class CircularDependencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tasks
        $this->task1 = Task::create([
            'title' => 'Task 1',
            'description' => 'First task',
            'status' => TaskStatus::PENDING,
        ]);
        
        $this->task2 = Task::create([
            'title' => 'Task 2', 
            'description' => 'Second task',
            'status' => TaskStatus::PENDING,
        ]);
        
        $this->task3 = Task::create([
            'title' => 'Task 3',
            'description' => 'Third task', 
            'status' => TaskStatus::PENDING,
        ]);
        
        $this->task4 = Task::create([
            'title' => 'Task 4',
            'description' => 'Fourth task',
            'status' => TaskStatus::PENDING,
        ]);
    }

    /** @test */
    public function it_detects_direct_circular_dependency()
    {
        // Create Task 1 → Task 2
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task2->id
        ]);

        // Test: Task 2 → Task 1 should create circular dependency
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task2->id, $this->task1->id);
        
        $this->assertTrue($wouldCreateCircular, 'Direct circular dependency should be detected');
    }

    /** @test */
    public function it_detects_indirect_circular_dependency()
    {
        // Create chain: Task 1 → Task 2 → Task 3
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task2->id
        ]);
        
        TaskDependency::create([
            'task_id' => $this->task2->id,
            'depends_on_task_id' => $this->task3->id
        ]);

        // Test: Task 3 → Task 1 should create circular dependency (Task 1 → Task 2 → Task 3 → Task 1)
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task3->id, $this->task1->id);
        
        $this->assertTrue($wouldCreateCircular, 'Indirect circular dependency should be detected');
    }

    /** @test */
    public function it_detects_self_dependency()
    {
        // Test: Task 1 → Task 1 should be detected as circular
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task1->id, $this->task1->id);
        
        $this->assertTrue($wouldCreateCircular, 'Self-dependency should be detected as circular');
    }

    /** @test */
    public function it_allows_valid_dependencies()
    {
        // Create Task 1 → Task 2
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task2->id
        ]);

        // Test: Task 3 → Task 1 should be allowed (no circular dependency)
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task3->id, $this->task1->id);
        
        $this->assertFalse($wouldCreateCircular, 'Valid dependency should be allowed');
    }

    /** @test */
    public function it_allows_parallel_dependencies()
    {
        // Create Task 1 → Task 3 and Task 2 → Task 3
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task3->id
        ]);
        
        TaskDependency::create([
            'task_id' => $this->task2->id,
            'depends_on_task_id' => $this->task3->id
        ]);

        // Test: Task 4 → Task 1 should be allowed (no circular dependency)
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task4->id, $this->task1->id);
        
        $this->assertFalse($wouldCreateCircular, 'Parallel dependencies should be allowed');
    }

    /** @test */
    public function observer_prevents_circular_dependency_creation()
    {
        // Create Task 1 → Task 2
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task2->id
        ]);

        // Attempt to create Task 2 → Task 1 (should be blocked by observer)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create dependency: would create circular dependency');
        
        TaskDependency::create([
            'task_id' => $this->task2->id,
            'depends_on_task_id' => $this->task1->id
        ]);
    }

    /** @test */
    public function observer_prevents_self_dependency_creation()
    {
        // Attempt to create Task 1 → Task 1 (should be blocked by observer)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A task cannot depend on itself');
        
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task1->id
        ]);
    }

    /** @test */
    public function it_handles_complex_dependency_chains()
    {
        // Create complex chain: Task 1 → Task 2 → Task 3 → Task 4
        TaskDependency::create([
            'task_id' => $this->task1->id,
            'depends_on_task_id' => $this->task2->id
        ]);
        
        TaskDependency::create([
            'task_id' => $this->task2->id,
            'depends_on_task_id' => $this->task3->id
        ]);
        
        TaskDependency::create([
            'task_id' => $this->task3->id,
            'depends_on_task_id' => $this->task4->id
        ]);

        // Test: Task 4 → Task 1 should create circular dependency
        $wouldCreateCircular = Task::wouldCreateCircularDependency($this->task4->id, $this->task1->id);
        
        $this->assertTrue($wouldCreateCircular, 'Complex circular dependency should be detected');
        
        // Test: Task 4 → Task 2 should create circular dependency  
        $wouldCreateCircular2 = Task::wouldCreateCircularDependency($this->task4->id, $this->task2->id);
        
        $this->assertTrue($wouldCreateCircular2, 'Complex circular dependency should be detected at any point');
    }
}