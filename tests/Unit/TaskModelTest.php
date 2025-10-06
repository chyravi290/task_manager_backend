<?php

namespace Tests\Unit;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_task()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => '2025-10-10',
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test Description', $task->description);
        $this->assertEquals('pending', $task->status);
        $this->assertNotNull($task->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = ['title', 'description', 'due_date', 'status'];

        $this->assertEquals($fillable, (new Task())->getFillable());
    }

    /** @test */
    public function it_casts_due_date_to_date()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'due_date' => '2025-10-10'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $task->due_date);
        $this->assertEquals('2025-10-10', $task->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_normalizes_status_to_lowercase()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'status' => 'PENDING'
        ]);

        $this->assertEquals('pending', $task->status);
    }

    /** @test */
    public function it_handles_null_due_date()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'due_date' => null
        ]);

        $this->assertNull($task->due_date);
    }

    /** @test */
    public function it_handles_null_description()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => null
        ]);

        $this->assertNull($task->description);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $task = Task::create([
            'title' => 'Test Task'
        ]);

        $this->assertNotNull($task->created_at);
        $this->assertNotNull($task->updated_at);
    }

    /** @test */
    public function it_can_be_updated()
    {
        $task = Task::create([
            'title' => 'Original Title',
            'status' => 'pending'
        ]);

        $task->update([
            'title' => 'Updated Title',
            'status' => 'completed'
        ]);

        $this->assertEquals('Updated Title', $task->title);
        $this->assertEquals('completed', $task->status);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $task = Task::create([
            'title' => 'Task to Delete'
        ]);

        $taskId = $task->id;
        $task->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    /** @test */
    public function it_can_be_found_by_id()
    {
        $task = Task::create([
            'title' => 'Findable Task'
        ]);

        $foundTask = Task::find($task->id);

        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertEquals($task->id, $foundTask->id);
        $this->assertEquals('Findable Task', $foundTask->title);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_id()
    {
        $task = Task::find(999);

        $this->assertNull($task);
    }

    /** @test */
    public function it_can_be_queried_by_status()
    {
        Task::create(['title' => 'Pending Task', 'status' => 'pending']);
        Task::create(['title' => 'Completed Task', 'status' => 'completed']);
        Task::create(['title' => 'Another Pending Task', 'status' => 'pending']);

        $pendingTasks = Task::where('status', 'pending')->get();
        $completedTasks = Task::where('status', 'completed')->get();

        $this->assertCount(2, $pendingTasks);
        $this->assertCount(1, $completedTasks);
    }

    /** @test */
    public function it_can_be_queried_by_due_date()
    {
        Task::create([
            'title' => 'Early Task',
            'due_date' => '2025-10-01'
        ]);
        Task::create([
            'title' => 'Late Task',
            'due_date' => '2025-10-31'
        ]);

        $earlyTasks = Task::where('due_date', '<=', '2025-10-15')->get();
        $lateTasks = Task::where('due_date', '>', '2025-10-15')->get();

        $this->assertCount(1, $earlyTasks);
        $this->assertCount(1, $lateTasks);
    }

    /** @test */
    public function it_can_be_ordered_by_due_date()
    {
        Task::create(['title' => 'Task 3', 'due_date' => '2025-10-15']);
        Task::create(['title' => 'Task 1', 'due_date' => '2025-10-01']);
        Task::create(['title' => 'Task 2', 'due_date' => '2025-10-10']);

        $orderedTasks = Task::orderBy('due_date')->get();

        $this->assertEquals('Task 1', $orderedTasks[0]->title);
        $this->assertEquals('Task 2', $orderedTasks[1]->title);
        $this->assertEquals('Task 3', $orderedTasks[2]->title);
    }

    /** @test */
    public function it_handles_mass_assignment_protection()
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => '2025-10-10',
            'status' => 'pending',
            'created_at' => '2025-01-01', // This should be ignored
            'updated_at' => '2025-01-01'  // This should be ignored
        ];

        $task = Task::create($taskData);

        // Only fillable attributes should be set
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test Description', $task->description);
        $this->assertEquals('pending', $task->status);

        // Timestamps should be set by Laravel, not by our input
        $this->assertNotEquals('2025-01-01', $task->created_at->format('Y-m-d'));
        $this->assertNotEquals('2025-01-01', $task->updated_at->format('Y-m-d'));
    }

    /** @test */
    public function it_can_use_factory()
    {
        $task = Task::factory()->create();

        $this->assertInstanceOf(Task::class, $task);
        $this->assertNotNull($task->title);
        $this->assertNotNull($task->status);
    }

    /** @test */
    public function it_can_use_factory_with_specific_attributes()
    {
        $task = Task::factory()->create([
            'title' => 'Factory Task',
            'status' => 'completed'
        ]);

        $this->assertEquals('Factory Task', $task->title);
        $this->assertEquals('completed', $task->status);
    }
}
