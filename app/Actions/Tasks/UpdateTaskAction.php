<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Arr;

class UpdateTaskAction
{
    /**
     * Field edits only: status moves exclusively through
     * TransitionTaskStatusAction, assignment through AssignTaskAction.
     *
     * @param  array<string, mixed>  $attributes  validated Update payload
     */
    public function handle(Task $task, array $attributes): Task
    {
        $task->fill(Arr::only($attributes, ['title', 'description', 'priority', 'due_at']))->save();

        return $task->refresh();
    }
}
