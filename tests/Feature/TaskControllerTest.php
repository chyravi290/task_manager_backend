<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_get_all_tasks()
    {
        // Create test tasks
        Task::factory()->create(['title' => 'Task 1', 'status' => 'pending']);
        Task::factory()->create(['title' => 'Task 2', 'status' => 'completed']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'due_date',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Tasks retrieved successfully'
                ]);
    }

    /** @test */
    public function it_can_filter_tasks_by_status()
    {
        Task::factory()->create(['title' => 'Pending Task', 'status' => 'pending']);
        Task::factory()->create(['title' => 'Completed Task', 'status' => 'completed']);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Pending Task', $data[0]['title']);
        $this->assertEquals('pending', $data[0]['status']);
    }

    /** @test */
    public function it_can_filter_tasks_by_due_date_range()
    {
        Task::factory()->create([
            'title' => 'Task 1',
            'due_date' => '2025-10-10'
        ]);
        Task::factory()->create([
            'title' => 'Task 2',
            'due_date' => '2025-10-15'
        ]);
        Task::factory()->create([
            'title' => 'Task 3',
            'due_date' => '2025-10-20'
        ]);

        $response = $this->getJson('/api/tasks?due_date_from=2025-10-12&due_date_to=2025-10-18');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Task 2', $data[0]['title']);
    }

    /** @test */
    public function it_can_create_a_task_with_valid_data()
    {
        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => '2025-10-10',
            'status' => 'pending'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'due_date',
                        'status',
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'New Task',
                        'description' => 'Task description',
                        'status' => 'pending'
                    ],
                    'message' => 'Task created successfully'
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_create_a_task_with_minimal_data()
    {
        $taskData = [
            'title' => 'Minimal Task'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'Minimal Task',
                        'status' => 'pending'
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Minimal Task',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_rejects_task_creation_with_backdated_due_date()
    {
        $taskData = [
            'title' => 'Backdated Task',
            'due_date' => '2025-01-01' // Past date
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['due_date'])
                ->assertJson([
                    'message' => 'The due date must be today or in the future.'
                ]);

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Backdated Task'
        ]);
    }

    /** @test */
    public function it_rejects_task_creation_without_title()
    {
        $taskData = [
            'description' => 'Task without title'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function it_rejects_task_creation_with_invalid_status()
    {
        $taskData = [
            'title' => 'Invalid Status Task',
            'status' => 'invalid_status'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_rejects_task_creation_with_invalid_due_date_format()
    {
        $taskData = [
            'title' => 'Invalid Date Task',
            'due_date' => 'not-a-date'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['due_date']);
    }

    /** @test */
    public function it_can_get_a_specific_task()
    {
        $task = Task::factory()->create(['title' => 'Specific Task']);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $task->id,
                        'title' => 'Specific Task'
                    ],
                    'message' => 'Task retrieved successfully'
                ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_task()
    {
        $response = $this->getJson('/api/tasks/999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Task not found'
                ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_task_id()
    {
        $response = $this->getJson('/api/tasks/invalid-id');

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid task ID'
                ]);
    }

    /** @test */
    public function it_can_update_task_status()
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $task->id,
                        'status' => 'completed'
                    ],
                    'message' => 'Task status updated successfully'
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_rejects_status_update_with_invalid_status()
    {
        $task = Task::factory()->create();

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_rejects_status_update_without_status()
    {
        $task = Task::factory()->create();

        $response = $this->patchJson("/api/tasks/{$task->id}/status", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_task_status()
    {
        $response = $this->patchJson('/api/tasks/999/status', [
            'status' => 'completed'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Task not found'
                ]);
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        $task = Task::factory()->create(['title' => 'Task to Delete']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task deleted successfully'
                ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_task()
    {
        $response = $this->deleteJson('/api/tasks/999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Task not found'
                ]);
    }

    /** @test */
    public function it_returns_422_when_deleting_with_invalid_id()
    {
        $response = $this->deleteJson('/api/tasks/invalid-id');

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid task ID'
                ]);
    }

    /** @test */
    public function it_accepts_today_as_valid_due_date()
    {
        $today = now()->format('Y-m-d');

        $taskData = [
            'title' => 'Today Task',
            'due_date' => $today
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'Today Task',
                        'due_date' => $today . 'T00:00:00.000000Z'
                    ]
                ]);
    }

    /** @test */
    public function it_accepts_future_dates_as_valid_due_date()
    {
        $futureDate = now()->addDays(7)->format('Y-m-d');

        $taskData = [
            'title' => 'Future Task',
            'due_date' => $futureDate
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'Future Task'
                    ]
                ]);
    }

    /** @test */
    public function it_handles_empty_query_parameters_gracefully()
    {
        Task::factory()->count(3)->create();

        $response = $this->getJson('/api/tasks?status=&due_date_from=&due_date_to=');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function it_validates_query_parameters()
    {
        $response = $this->getJson('/api/tasks?status=invalid&due_date_from=invalid-date');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status', 'due_date_from']);
    }
}
