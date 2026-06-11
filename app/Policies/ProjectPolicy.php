<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\ResolvesTeamMembership;

class ProjectPolicy
{
    use ResolvesTeamMembership;

    public function viewAny(User $user, Team $team): bool
    {
        return $this->ownsTeam($user, $team)
            || $this->canInTeam($user, $team, 'project.view');
    }

    public function create(User $user, Team $team): bool
    {
        return $this->ownsTeam($user, $team)
            || $this->canInTeam($user, $team, 'project.create');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allows($user, $project, 'project.view');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allows($user, $project, 'project.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allows($user, $project, 'project.delete');
    }

    /** Two-gate check against the team re-derived from the project itself. */
    private function allows(User $user, Project $project, string $permission): bool
    {
        $team = $project->team;
        if (! $team instanceof Team) {
            return false;
        }

        return $this->ownsTeam($user, $team)
            || $this->canInTeam($user, $team, $permission);
    }
}
