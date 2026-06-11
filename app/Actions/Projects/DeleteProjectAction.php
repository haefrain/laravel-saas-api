<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;

class DeleteProjectAction
{
    /**
     * Soft-deletes the project. Centralised so the cascade to child resources
     * (tasks, comments) lands here when those models exist.
     */
    public function handle(Project $project): void
    {
        $project->delete();
    }
}
