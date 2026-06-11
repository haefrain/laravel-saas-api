<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lets an authenticated user create a team and become its owner', function () {
    seedRolesAndPermissions();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/teams', ['name' => 'Rocket Labs'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rocket Labs')
        ->assertJsonPath('data.role', 'owner');

    $team = Team::firstOrFail();
    expect($team->owner_id)->toBe($user->id)
        ->and($user->fresh()->current_team_id)->toBe($team->id)
        ->and($user->roleForTeam($team))->toBe(TeamRole::Owner);
});

it('lists only the teams the caller belongs to', function () {
    [$teamA, $owner] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B'); // owned by someone else

    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/teams')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Team A');

    expect($teamB->owner_id)->not->toBe($owner->id);
});

it('lets the owner update and soft-delete the team', function () {
    [$team, $owner] = makeTeamWithOwner();
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/teams/{$team->id}", ['name' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/v1/teams/{$team->id}")->assertNoContent();
    $this->assertSoftDeleted('teams', ['id' => $team->id]);
});

it('lets an admin update the team but not delete it', function () {
    [$team] = makeTeamWithOwner();
    $admin = addTeamMember($team, TeamRole::Admin);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/teams/{$team->id}", ['name' => 'Admin Renamed'])->assertOk();
    $this->deleteJson("/api/v1/teams/{$team->id}")->assertForbidden();
});

it('forbids a member from updating or deleting the team', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->patchJson("/api/v1/teams/{$team->id}", ['name' => 'Nope'])->assertForbidden();
    $this->deleteJson("/api/v1/teams/{$team->id}")->assertForbidden();
});

it('shows the team with the caller-specific role', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->getJson("/api/v1/teams/{$team->id}")
        ->assertOk()
        ->assertJsonPath('data.role', 'member')
        ->assertJsonPath('data.members_count', 2);
});
