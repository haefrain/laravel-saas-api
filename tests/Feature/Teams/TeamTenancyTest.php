<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

it('rejects unauthenticated access to teams', function () {
    $this->getJson('/api/v1/teams')->assertUnauthorized();
});

it('forbids a non-member from touching another team (membership gate)', function () {
    [$teamA] = makeTeamWithOwner('Team A');
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/teams/{$teamA->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'team_forbidden');
});

it('denies the owner of team A every action on team B', function () {
    [$teamA, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    Sanctum::actingAs($ownerA);

    // Owner of A is not a member of B: the membership gate forbids before the policy.
    $this->getJson("/api/v1/teams/{$teamB->id}")->assertForbidden();
    $this->patchJson("/api/v1/teams/{$teamB->id}", ['name' => 'Hijack'])->assertForbidden();
    $this->deleteJson("/api/v1/teams/{$teamB->id}")->assertForbidden();

    // And the policy itself denies cross-team, independent of the middleware —
    // proving the owner short-circuit is scoped to the resource's own team.
    expect(Gate::forUser($ownerA)->allows('update', $teamB))->toBeFalse()
        ->and(Gate::forUser($ownerA)->allows('delete', $teamB))->toBeFalse()
        ->and($teamA->owner_id)->toBe($ownerA->id);
});

it('never leaves spatie global team id mutated after an authorization', function () {
    [$teamA, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');

    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId(null);

    Gate::forUser($ownerA)->allows('update', $teamB);

    expect($registrar->getPermissionsTeamId())->toBeNull();
});

it('returns 404 for a team that does not exist', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $missingId = Team::max('id') + 999;
    $this->getJson("/api/v1/teams/{$missingId}")->assertNotFound();
});
