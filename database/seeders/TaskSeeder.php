<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory()->createMany([
            ['title' => 'Prepare project report', 'description' => 'Prepare report for Q4', 'due_date' => now()->addDays(3), 'status' => 'pending'],
            ['title' => 'Send client email', 'description' => 'Update client on project status', 'due_date' => now()->addDay(), 'status' => 'completed'],
        ]);
    }
}
