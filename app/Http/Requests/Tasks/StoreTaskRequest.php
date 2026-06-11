<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Team;
use App\Rules\MemberOfTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
        $team = $this->route('team');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_id' => [
                'nullable',
                'integer',
                $team instanceof Team ? new MemberOfTeam($team) : 'prohibited',
            ],
            'priority' => ['sometimes', 'integer', 'between:1,4'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            // Only pre-work states may be set at creation; everything else
            // must travel through the transition endpoint.
            'status' => ['sometimes', Rule::in(['todo', 'in_progress'])],
        ];
    }
}
