<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates a team and makes its creator the owner — in one transaction, writing
 * all three sources of truth atomically: teams.owner_id, the team_user pivot
 * (membership mirror) and the spatie team-scoped owner role.
 */
class CreateTeamAction
{
    public function handle(User $owner, string $name, ?string $slug = null): Team
    {
        return DB::transaction(function () use ($owner, $name, $slug): Team {
            $team = Team::create([
                'name' => $name,
                'slug' => $slug ?? $this->uniqueSlug($name),
                'owner_id' => $owner->getKey(),
            ]);

            // Membership mirror — role is a server-derived value, never request input.
            $team->members()->attach($owner->getKey(), [
                'membership_role' => TeamRole::Owner->value,
            ]);

            $this->assignOwnerRole($owner, $team);

            if ($owner->current_team_id === null) {
                $owner->forceFill(['current_team_id' => $team->getKey()])->save();
            }

            return $team;
        });
    }

    private function assignOwnerRole(User $owner, Team $team): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($team->getKey());
            $owner->unsetRelation('roles')->unsetRelation('permissions');
            $owner->syncRoles(['owner']);
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    private function uniqueSlug(string $name): string
    {
        return Str::slug($name).'-'.Str::lower(Str::random(6));
    }
}
