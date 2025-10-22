<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Task;

class TaskDependencyRequest extends FormRequest
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
        $taskId = $this->route('task') ? $this->route('task')->id : null;
        
        return [
            'depends_on_task_id' => [
                'required',
                'integer',
                'exists:tasks,id',
                function ($attribute, $value, $fail) use ($taskId) {
                    // Prevent self-dependency
                    if ($value == $taskId) {
                        $fail('A task cannot depend on itself.');
                        return;
                    }
                    
                    // Check for circular dependency using service
                    $service = app(\App\Services\TaskDependencyService::class);
                    if ($taskId && $service->wouldCreateCircularDependency($taskId, $value)) {
                        $fail('This dependency would create a circular dependency.');
                        return;
                    }
                },
                // Prevent duplicate dependencies
                Rule::unique('task_dependencies')->where(function ($query) use ($taskId) {
                    return $query->where('task_id', $taskId);
                })
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'depends_on_task_id.required' => 'The dependency task is required.',
            'depends_on_task_id.integer' => 'The dependency task must be a valid task ID.',
            'depends_on_task_id.exists' => 'The selected task does not exist.',
            'depends_on_task_id.unique' => 'This dependency already exists.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('task')) {
            $this->merge([
                'task_id' => $this->route('task')->id
            ]);
        }
    }
}
