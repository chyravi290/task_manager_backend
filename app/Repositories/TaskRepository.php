<?php

namespace App\Repositories;

use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskRepository implements TaskRepositoryInterface
{
    public function getAllTasks(array $filters): Collection
    {
        $query = Task::query();

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by due date range
        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        // Filter by title (partial match)
        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        return $query->orderBy('due_date', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public function getTaskById($id): Task
    {
        $task = Task::find($id);

        if (!$task) {
            throw new ModelNotFoundException("Task with ID {$id} not found.");
        }

        return $task;
    }

    public function createTask(array $data): Task
    {
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        return Task::create($data);
    }

    public function updateTaskStatus($id, $status): Task
    {
        $task = $this->getTaskById($id);
        $task->status = $status;
        $task->save();

        return $task->fresh();
    }

    public function deleteTask($id): bool
    {
        $task = $this->getTaskById($id);
        return $task->delete();
    }
}
