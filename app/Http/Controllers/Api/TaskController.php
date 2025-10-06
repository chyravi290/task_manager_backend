<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Interfaces\TaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class TaskController extends Controller
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Display a listing of tasks.
     */
    public function index(TaskRequest $request): JsonResponse
    {
        try {
            $tasks = $this->taskRepository->getAllTasks($request->validated());

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Tasks retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created task.
     */
    public function store(TaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskRepository->createTask($request->validated());

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task created successfully'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task.
     */
    public function show($id): JsonResponse
    {
        try {
            $this->validateId($id);
            $task = $this->taskRepository->getTaskById($id);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task ID',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified task's status.
     */
    public function updateStatus(TaskRequest $request, $id): JsonResponse
    {
        try {
            $this->validateId($id);
            $task = $this->taskRepository->updateTaskStatus($id, $request->validated()['status']);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task status updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task ID',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->validateId($id);
            $deleted = $this->taskRepository->deleteTask($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete task'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task ID',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate task ID parameter.
     */
    private function validateId($id): void
    {
        if (!is_numeric($id) || $id <= 0) {
            throw ValidationException::withMessages([
                'id' => ['The task ID must be a positive integer.']
            ]);
        }
    }
}
