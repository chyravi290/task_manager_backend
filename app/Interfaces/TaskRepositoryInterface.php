<?php

namespace App\Interfaces;

interface TaskRepositoryInterface
{
    public function getAllTasks(array $filters);
    public function getTaskById($id);
    public function createTask(array $data);
    public function updateTaskStatus($id, $status);
    public function deleteTask($id);
}
