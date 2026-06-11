<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Events\TaskAssigned;
use App\Models\Task;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignTaskAction
{
    public function handle(Task $task, ?int $assigneeId, User $actor): Task
    {
        // Re-assert against the task's own team (the FormRequest validated
        // against the route team): defence in depth for cross-tenant targets.
        if ($assigneeId !== null && ! $this->isTeamMember($task->team_id, $assigneeId)) {
            throw ValidationException::withMessages([
                'assignee_id' => 'The selected assignee must be a member of this team.',
            ]);
        }

        $previous = $task->assignee_id;
        $task->assignee_id = $assigneeId;
        $task->save();

        if ($assigneeId !== null && $assigneeId !== $previous) {
            event(new TaskAssigned($task->id, $assigneeId, $actor->getKey()));
        }

        return $task;
    }

    private function isTeamMember(int $teamId, int $userId): bool
    {
        return TeamUser::query()
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->exists();
    }
}
