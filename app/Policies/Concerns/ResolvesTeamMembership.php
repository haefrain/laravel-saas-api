<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Shared two-gate authorization helpers. Every decision answers BOTH:
 *  (a) is the actor a member of the RESOURCE's own team? and
 *  (b) does the actor's team-scoped role grant the ability?
 *
 * The team is always re-derived from the resource (not the route), and spatie's
 * process-global team id is saved/restored around every read so no policy ever
 * leaves global tenant state mutated.
 */
trait ResolvesTeamMembership
{
    private function roleIn(User $user, Team $team): ?TeamRole
    {
        if (! $user->belongsToTeam($team)) {
            return null; // cross-team / IDOR denial — never reaches the owner check
        }

        return $user->roleForTeam($team);
    }

    /** Owner short-circuit, scoped to THIS resource's team (not Gate::before). */
    private function ownsTeam(User $user, Team $team): bool
    {
        return $this->roleIn($user, $team) === TeamRole::Owner;
    }

    /** Permission check with save/restore of spatie's global team id. */
    private function canInTeam(User $user, Team $team, string $permission): bool
    {
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($team->getKey());
            $user->unsetRelation('roles')->unsetRelation('permissions');

            return $user->can($permission);
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
