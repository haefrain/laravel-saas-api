<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Team;
use App\Rules\MemberOfTeam;
use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
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
            // present (not required) so an explicit null unassigns.
            'assignee_id' => [
                'present',
                'nullable',
                'integer',
                $team instanceof Team ? new MemberOfTeam($team) : 'prohibited',
            ],
        ];
    }
}
