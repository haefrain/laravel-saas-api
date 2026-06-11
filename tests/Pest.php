<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeamAction;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full framework via Tests\TestCase and run against a
| transactional, freshly-migrated MySQL "testing" database (RefreshDatabase),
| so every test starts from a clean, isolated state.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Domain helpers
|--------------------------------------------------------------------------
*/

function seedRolesAndPermissions(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
}

/**
 * @return array{0: Team, 1: User}
 */
function makeTeamWithOwner(string $name = 'Acme'): array
{
    seedRolesAndPermissions();
    $owner = User::factory()->create();
    $team = app(CreateTeamAction::class)->handle($owner, $name);

    return [$team, $owner];
}

/** Attach an extra user to a team with a given role (pivot + spatie, team-scoped). */
function addTeamMember(Team $team, TeamRole $role): User
{
    $user = User::factory()->create();
    $team->members()->attach($user->getKey(), ['membership_role' => $role->value]);

    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    try {
        $registrar->setPermissionsTeamId($team->getKey());
        $user->syncRoles([$role->value]);
    } finally {
        $registrar->setPermissionsTeamId($previous);
    }

    return $user;
}
