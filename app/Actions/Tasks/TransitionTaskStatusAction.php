<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Events\TaskStatusChanged;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Models\User;

class TransitionTaskStatusAction
{
    public function handle(Task $task, TaskStatus $to, User $actor): Task
    {
        $from = $task->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidTaskTransitionException($from, $to);
        }

        $task->status = $to;
        // completed_at is owned by this action: stamped on done, cleared on
        // any edge out of done (reopen) — it is not mass assignable anywhere.
        $task->completed_at = $to === TaskStatus::Done ? now() : null;
        $task->save();

        event(new TaskStatusChanged($task->id, $from, $to, $actor->getKey()));

        return $task;
    }
}
