<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Helpers\XssHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the service layer
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        // If user is a regular user, only allow status updates
        if ($user && $user->role === UserRole::USER) {
            return [
                'status' => [
                    'required',
                    Rule::enum(TaskStatus::class)
                ]
            ];
        }

        // Managers can update all fields
        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:65535' // TEXT field max length
            ],
            'assignee_user' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'status' => [
                'sometimes',
                Rule::enum(TaskStatus::class)
            ],
            'due_date' => [
                'sometimes',
                'nullable',
                'date'
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Task title must be at least 3 characters.',
            'title.max' => 'Task title cannot exceed 255 characters.',
            'description.max' => 'Task description is too long.',
            'assignee_user.exists' => 'The selected user does not exist.',
            'assignee_user.integer' => 'The assignee must be a valid user ID.',
            'status.required' => 'Task status is required.',
            'status.enum' => 'Invalid task status. Valid options are: ' . implode(', ', TaskStatus::values()),
            'due_date.date' => 'Due date must be a valid date.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'assignee_user' => 'assignee',
            'due_date' => 'due date'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize string inputs to prevent XSS
        $sanitizedData = [];
        
        if ($this->has('title')) {
            $sanitizedData['title'] = XssHelper::sanitize($this->input('title'));
        }
        
        if ($this->has('description')) {
            $description = $this->input('description');
            $sanitizedData['description'] = $description === '' ? null : XssHelper::sanitize($description);
        }

        // Convert empty strings to null for nullable fields
        if ($this->has('assignee_user') && $this->input('assignee_user') === '') {
            $sanitizedData['assignee_user'] = null;
        }

        if ($this->has('due_date') && $this->input('due_date') === '') {
            $sanitizedData['due_date'] = null;
        }
        
        if (!empty($sanitizedData)) {
            $this->merge($sanitizedData);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            
            if ($user && $user->role === UserRole::USER) {
                // Users can only update their own tasks and only the status field
                $allowedFields = ['status'];
                $submittedFields = array_keys($this->all());
                $disallowedFields = array_diff($submittedFields, $allowedFields);
                
                if (!empty($disallowedFields)) {
                    $validator->errors()->add(
                        'permission', 
                        'You can only update the status field of tasks assigned to you.'
                    );
                }
            }
        });
    }
}