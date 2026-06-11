<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionTaskRequest extends FormRequest
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
        // Lifecycle membership only; edge legality is the state machine's
        // job inside TransitionTaskStatusAction.
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }
}
