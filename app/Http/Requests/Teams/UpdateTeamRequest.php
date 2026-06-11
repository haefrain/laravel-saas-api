<?php

declare(strict_types=1);

namespace App\Http\Requests\Teams;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by TeamPolicy in the controller.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $team = $this->route('team');
        $teamId = $team instanceof Team ? $team->getKey() : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'alpha_dash',
                'max:255',
                Rule::unique('teams', 'slug')->ignore($teamId),
            ],
        ];
    }
}
