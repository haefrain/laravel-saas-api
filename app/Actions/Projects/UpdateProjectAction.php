<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Arr;

class UpdateProjectAction
{
    /**
     * @param  array<string, mixed>  $attributes  validated Update payload
     */
    public function handle(Project $project, array $attributes): Project
    {
        // The slug is immutable after creation: clients may hold references.
        $project->fill(Arr::only($attributes, ['name', 'description', 'status']))->save();

        return $project->refresh();
    }
}
