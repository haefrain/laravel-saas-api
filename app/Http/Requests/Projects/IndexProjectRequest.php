<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is the ProjectPolicy's job (controller authorize call).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'sort' => ['sometimes', Rule::in(['name', '-name', 'created_at', '-created_at'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
