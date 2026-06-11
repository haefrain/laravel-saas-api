<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\ResolvesTeamMembership;

class TeamPolicy
{
    use ResolvesTeamMembership;

    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->ownsTeam($user, $team)
            || $this->canInTeam($user, $team, 'team.update');
    }

    public function delete(User $user, Team $team): bool
    {
        // Deleting a team is reserved for its owner.
        return $this->ownsTeam($user, $team);
    }
}
