<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\ResolvesTeamMembership;

class TaskCommentPolicy
{
    use ResolvesTeamMembership;

    public function viewAny(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'comment.view');
    }

    public function create(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'comment.create');
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        // Owners/admins hold the comment.delete grant; members only their own.
        if ($this->allowsOnTeam($user, $comment->team, 'comment.delete')) {
            return true;
        }

        return $comment->team instanceof Team
            && $this->roleIn($user, $comment->team) !== null
            && $comment->user_id === $user->getKey();
    }

    /** Two-gate check against the team re-derived from the resource itself. */
    private function allowsOnTeam(User $user, ?Team $team, string $permission): bool
    {
        if (! $team instanceof Team) {
            return false;
        }

        return $this->ownsTeam($user, $team)
            || $this->canInTeam($user, $team, $permission);
    }
}
