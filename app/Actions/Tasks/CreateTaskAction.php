<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class CreateTaskAction
{
    /**
     * @param  array<string, mixed>  $attributes  validated Store payload
     */
    public function handle(Project $project, User $creator, array $attributes): Task
    {
        $task = new Task([
            'title' => (string) $attributes['title'],
            'description' => isset($attributes['description']) ? (string) $attributes['description'] : null,
            'status' => isset($attributes['status'])
                ? TaskStatus::from((string) $attributes['status'])
                : TaskStatus::Todo,
            'priority' => (int) ($attributes['priority'] ?? 2),
            'due_at' => $attributes['due_at'] ?? null,
            'assignee_id' => $attributes['assignee_id'] ?? null,
        ]);

        // team_id is derived from the parent project — never from the request
        // or the tenant context — so it cannot drift cross-tenant.
        $task->team_id = $project->team_id;
        $task->project_id = $project->getKey();
        $task->created_by = $creator->getKey();
        $task->save();

        if ($task->assignee_id !== null) {
            event(new TaskAssigned($task->id, $task->assignee_id, $creator->getKey()));
        }

        return $task;
    }
}
