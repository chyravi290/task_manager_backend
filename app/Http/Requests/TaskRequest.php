<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        // Rules for creating tasks (POST)
        if ($this->isMethod('post')) {
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'due_date' => 'nullable|date|after_or_equal:today',
                'status' => 'nullable|in:pending,completed',
            ];
        }

        // Rules for updating task status (PATCH)
        if ($this->isMethod('patch') && $this->route('id')) {
            $rules = [
                'status' => 'required|in:pending,completed',
            ];
        }

        // Rules for query parameters (GET)
        if ($this->isMethod('get')) {
            $rules = [
                'status' => 'nullable|in:pending,completed',
                'due_date_from' => 'nullable|date',
                'due_date_to' => 'nullable|date|after_or_equal:due_date_from',
                'title' => 'nullable|string|max:255',
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Task creation messages
            'title.required' => 'The task title is required.',
            'title.max' => 'The task title may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',
            'due_date.date' => 'The due date must be a valid date.',
            'due_date.after_or_equal' => 'The due date must be today or in the future.',
            'status.in' => 'The status must be either pending or completed.',

            // Status update messages
            'status.required' => 'The status field is required when updating task status.',

            // Query parameter messages
            'due_date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'due_date_from' => 'start date',
            'due_date_to' => 'end date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status for new tasks if not provided
        if ($this->isMethod('post') && !$this->has('status')) {
            $this->merge([
                'status' => 'pending'
            ]);
        }

        // Normalize status to lowercase
        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->input('status'))
            ]);
        }
    }
}
