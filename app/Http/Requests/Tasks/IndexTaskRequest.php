<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexTaskRequest extends FormRequest
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
            // CSV of statuses, validated segment-by-segment in withValidator.
            'status' => ['sometimes', 'string', 'max:100'],
            // A user id, "me" (the caller) or "null" (unassigned).
            'assignee_id' => ['sometimes', 'string', 'regex:/^(me|null|\d+)$/'],
            'q' => ['sometimes', 'string', 'max:255'],
            'due_before' => ['sometimes', 'date'],
            'due_after' => ['sometimes', 'date'],
            'sort' => ['sometimes', Rule::in([
                'created_at', '-created_at', 'due_at', '-due_at', 'priority', '-priority',
            ])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = $this->input('status');
            if (! is_string($status) || $status === '') {
                return;
            }

            foreach (explode(',', $status) as $segment) {
                if (TaskStatus::tryFrom($segment) === null) {
                    $validator->errors()->add('status', "Invalid status \"{$segment}\".");

                    return;
                }
            }
        });
    }

    /**
     * @return list<TaskStatus>
     */
    public function statuses(): array
    {
        $status = $this->validated('status');
        if (! is_string($status) || $status === '') {
            return [];
        }

        return array_map(
            static fn (string $segment): TaskStatus => TaskStatus::from($segment),
            explode(',', $status),
        );
    }
}
