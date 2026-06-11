<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is the TaskPolicy's job (controller authorize call).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'integer', 'between:1,4'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            // Status and assignment have dedicated endpoints with their own
            // invariants (state machine, team-membership check).
            'status' => ['prohibited'],
            'assignee_id' => ['prohibited'],
        ];
    }
}
