<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TaskRepository $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskRepository = new TaskRepository();
    }

    /** @test */
    public function it_can_get_all_tasks_without_filters()
    {
        Task::factory()->count(3)->create();

        $tasks = $this->taskRepository->getAllTasks([]);

        $this->assertInstanceOf(Collection::class, $tasks);
        $this->assertCount(3, $tasks);
    }

    /** @test */
    public function it_can_filter_tasks_by_status()
    {
        Task::factory()->create(['status' => 'pending']);
        Task::factory()->create(['status' => 'completed']);
        Task::factory()->create(['status' => 'pending']);

        $tasks = $this->taskRepository->getAllTasks(['status' => 'pending']);

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn($task) => $task->status === 'pending'));
    }

    /** @test */
    public function it_can_filter_tasks_by_due_date_range()
    {
        Task::factory()->create(['due_date' => '2025-10-10']);
        Task::factory()->create(['due_date' => '2025-10-15']);
        Task::factory()->create(['due_date' => '2025-10-20']);

        $tasks = $this->taskRepository->getAllTasks([
            'due_date_from' => '2025-10-12',
            'due_date_to' => '2025-10-18'
        ]);

        $this->assertCount(1, $tasks);
        $this->assertEquals('2025-10-15', $tasks->first()->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_filter_tasks_by_title()
    {
        Task::factory()->create(['title' => 'Important Task']);
        Task::factory()->create(['title' => 'Regular Task']);
        Task::factory()->create(['title' => 'Another Important Task']);

        $tasks = $this->taskRepository->getAllTasks(['title' => 'Important']);

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn($task) => str_contains($task->title, 'Important')));
    }

    /** @test */
    public function it_orders_tasks_by_due_date_asc_then_created_at_desc()
    {
        $task1 = Task::factory()->create([
            'due_date' => '2025-10-15',
            'created_at' => now()->subDays(2)
        ]);
        $task2 = Task::factory()->create([
            'due_date' => '2025-10-10',
            'created_at' => now()->subDays(1)
        ]);
        $task3 = Task::factory()->create([
            'due_date' => '2025-10-15',
            'created_at' => now()->subDays(3)
        ]);

        $tasks = $this->taskRepository->getAllTasks([]);

        $this->assertEquals($task2->id, $tasks[0]->id); // Earliest due date
        $this->assertEquals($task1->id, $tasks[1]->id); // Same due date, newer created
        $this->assertEquals($task3->id, $tasks[2]->id); // Same due date, older created
    }

    /** @test */
    public function it_can_get_task_by_id()
    {
        $task = Task::factory()->create(['title' => 'Test Task']);

        $foundTask = $this->taskRepository->getTaskById($task->id);

        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertEquals($task->id, $foundTask->id);
        $this->assertEquals('Test Task', $foundTask->title);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_task()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Task with ID 999 not found.');

        $this->taskRepository->getTaskById(999);
    }

    /** @test */
    public function it_can_create_a_task()
    {
        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => '2025-10-10',
            'status' => 'pending'
        ];

        $task = $this->taskRepository->createTask($taskData);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('New Task', $task->title);
        $this->assertEquals('Task description', $task->description);
        $this->assertEquals('pending', $task->status);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_sets_default_status_when_creating_task()
    {
        $taskData = [
            'title' => 'Task without status'
        ];

        $task = $this->taskRepository->createTask($taskData);

        $this->assertEquals('pending', $task->status);
    }

    /** @test */
    public function it_can_update_task_status()
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $updatedTask = $this->taskRepository->updateTaskStatus($task->id, 'completed');

        $this->assertInstanceOf(Task::class, $updatedTask);
        $this->assertEquals('completed', $updatedTask->status);
        $this->assertEquals($task->id, $updatedTask->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_throws_exception_when_updating_nonexistent_task_status()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->taskRepository->updateTaskStatus(999, 'completed');
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        $task = Task::factory()->create(['title' => 'Task to Delete']);

        $result = $this->taskRepository->deleteTask($task->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_nonexistent_task()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->taskRepository->deleteTask(999);
    }

    /** @test */
    public function it_handles_empty_filters_gracefully()
    {
        Task::factory()->count(2)->create();

        $tasks = $this->taskRepository->getAllTasks([
            'status' => '',
            'due_date_from' => '',
            'due_date_to' => '',
            'title' => ''
        ]);

        $this->assertCount(2, $tasks);
    }

    /** @test */
    public function it_handles_null_filters_gracefully()
    {
        Task::factory()->count(2)->create();

        $tasks = $this->taskRepository->getAllTasks([
            'status' => null,
            'due_date_from' => null,
            'due_date_to' => null,
            'title' => null
        ]);

        $this->assertCount(2, $tasks);
    }

    /** @test */
    public function it_combines_multiple_filters_correctly()
    {
        Task::factory()->create([
            'title' => 'Important Pending Task',
            'status' => 'pending',
            'due_date' => '2025-10-15'
        ]);
        Task::factory()->create([
            'title' => 'Important Completed Task',
            'status' => 'completed',
            'due_date' => '2025-10-15'
        ]);
        Task::factory()->create([
            'title' => 'Regular Pending Task',
            'status' => 'pending',
            'due_date' => '2025-10-20'
        ]);

        $tasks = $this->taskRepository->getAllTasks([
            'title' => 'Important',
            'status' => 'pending',
            'due_date_from' => '2025-10-10',
            'due_date_to' => '2025-10-20'
        ]);

        $this->assertCount(1, $tasks);
        $this->assertEquals('Important Pending Task', $tasks->first()->title);
    }
}
