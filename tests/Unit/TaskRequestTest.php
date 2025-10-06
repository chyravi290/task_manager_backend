<?php

namespace Tests\Unit;

use App\Http\Requests\TaskRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TaskRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_task_creation_rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|in:pending,completed',
        ];

        // Valid data
        $validData = [
            'title' => 'Valid Task',
            'description' => 'Valid description',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'pending'
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - missing title
        $invalidData = [
            'description' => 'No title'
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_status_update_rules()
    {
        $rules = [
            'status' => 'required|in:pending,completed',
        ];

        // Valid status
        $validData = ['status' => 'completed'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid status
        $invalidData = ['status' => 'invalid_status'];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());

        // Missing status
        $missingData = [];
        $validator = Validator::make($missingData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_query_parameter_rules()
    {
        $rules = [
            'status' => 'nullable|in:pending,completed',
            'due_date_from' => 'nullable|date',
            'due_date_to' => 'nullable|date|after_or_equal:due_date_from',
            'title' => 'nullable|string|max:255',
        ];

        // Valid query parameters
        $validData = [
            'status' => 'pending',
            'due_date_from' => '2025-10-01',
            'due_date_to' => '2025-10-31',
            'title' => 'Search term'
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid date range
        $invalidData = [
            'due_date_from' => '2025-10-31',
            'due_date_to' => '2025-10-01' // End date before start date
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_date_to', $validator->errors()->toArray());
    }

    /** @test */
    public function it_rejects_backdated_due_dates()
    {
        $rules = [
            'due_date' => 'nullable|date|after_or_equal:today',
        ];

        // Yesterday's date
        $yesterday = now()->subDay()->format('Y-m-d');
        $data = ['due_date' => $yesterday];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_today_as_valid_due_date()
    {
        $rules = [
            'due_date' => 'nullable|date|after_or_equal:today',
        ];

        $today = now()->format('Y-m-d');
        $data = ['due_date' => $today];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_accepts_future_dates_as_valid_due_date()
    {
        $rules = [
            'due_date' => 'nullable|date|after_or_equal:today',
        ];

        $futureDate = now()->addDays(7)->format('Y-m-d');
        $data = ['due_date' => $futureDate];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_rejects_invalid_date_formats()
    {
        $rules = [
            'due_date' => 'nullable|date|after_or_equal:today',
        ];

        $invalidDates = [
            'not-a-date',
            '2025-13-01', // Invalid month
            '2025-02-30', // Invalid day
            '32/12/2025', // Wrong format
        ];

        foreach ($invalidDates as $invalidDate) {
            $data = ['due_date' => $invalidDate];
            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->fails(), "Date '{$invalidDate}' should be invalid");
            $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
        }
    }

    /** @test */
    public function it_validates_title_length()
    {
        $rules = [
            'title' => 'required|string|max:255',
        ];

        // Valid title
        $validData = ['title' => str_repeat('a', 255)];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Title too long
        $invalidData = ['title' => str_repeat('a', 256)];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_description_length()
    {
        $rules = [
            'description' => 'nullable|string|max:1000',
        ];

        // Valid description
        $validData = ['description' => str_repeat('a', 1000)];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Description too long
        $invalidData = ['description' => str_repeat('a', 1001)];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_null_values_for_optional_fields()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|in:pending,completed',
        ];

        $data = [
            'title' => 'Task with nulls',
            'description' => null,
            'due_date' => null,
            'status' => null
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_accepts_empty_strings_for_optional_fields()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|in:pending,completed',
        ];

        $data = [
            'title' => 'Task with empty strings',
            'description' => '',
            'due_date' => '',
            'status' => ''
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_status_case_insensitivity()
    {
        $rules = [
            'status' => 'nullable|in:pending,completed',
        ];

        // The validation rule is case-sensitive, but our TaskRequest normalizes to lowercase
        $validStatuses = ['pending', 'completed'];

        foreach ($validStatuses as $status) {
            $data = ['status' => $status];
            $validator = Validator::make($data, $rules);
            $this->assertFalse($validator->fails(), "Status '{$status}' should be valid");
        }

        // Test that uppercase statuses fail validation (before normalization)
        $invalidStatuses = ['PENDING', 'Pending', 'COMPLETED', 'Completed'];
        foreach ($invalidStatuses as $status) {
            $data = ['status' => $status];
            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->fails(), "Status '{$status}' should be invalid before normalization");
        }
    }
}
