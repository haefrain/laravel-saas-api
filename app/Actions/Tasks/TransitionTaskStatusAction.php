<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Events\TaskStatusChanged;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransitionTaskStatusAction
{
    public function handle(Task $task, TaskStatus $to, User $actor): Task
    {
        $from = $task->status;

        if (! $from->canTransitionTo($to)) {
            $allowed = array_map(
                static fn (TaskStatus $status): string => $status->value,
                $from->allowedTransitions(),
            );

            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot transition from %s to %s. Allowed: %s.',
                    $from->value,
                    $to->value,
                    implode(', ', $allowed),
                ),
            ]);
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
