<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\ResolvesTeamMembership;

class TaskPolicy
{
    use ResolvesTeamMembership;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->allowsOnTeam($user, $project->team, 'task.view');
    }

    public function create(User $user, Project $project): bool
    {
        return $this->allowsOnTeam($user, $project->team, 'task.create');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'task.view');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'task.update');
    }

    public function delete(User $user, Task $task): bool
    {
        // Members lack the task.delete grant but may delete their own tasks;
        // the creator check still requires membership of the task's team.
        if ($this->allowsOnTeam($user, $task->team, 'task.delete')) {
            return true;
        }

        return $task->team instanceof Team
            && $this->roleIn($user, $task->team) !== null
            && $task->created_by === $user->getKey();
    }

    public function assign(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'task.assign');
    }

    public function transition(User $user, Task $task): bool
    {
        return $this->allowsOnTeam($user, $task->team, 'task.transition');
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
