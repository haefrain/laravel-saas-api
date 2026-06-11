<?php

declare(strict_types=1);

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may create a team (and becomes its owner).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:255', 'unique:teams,slug'],
        ];
    }
}
