<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

it('returns 404 for another team\'s project under your own team path', function () {
    [$teamA, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $projectB = Project::factory()->for($teamB)->create();
    Sanctum::actingAs($ownerA);

    // Scoped route binding: {project} is resolved through team A's relation,
    // so a foreign id 404s at the router without leaking existence.
    $this->getJson("/api/v1/teams/{$teamA->id}/projects/{$projectB->id}")
        ->assertNotFound();
});

it('forbids a non-member from listing another team\'s projects', function () {
    [$teamB] = makeTeamWithOwner('Team B');
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/teams/{$teamB->id}/projects")
        ->assertForbidden()
        ->assertJsonPath('message', 'team_forbidden');
});

it('denies the owner of team A every project action on team B', function () {
    [, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $projectB = Project::factory()->for($teamB)->create();
    Sanctum::actingAs($ownerA);

    $this->getJson("/api/v1/teams/{$teamB->id}/projects")->assertForbidden();
    $this->postJson("/api/v1/teams/{$teamB->id}/projects", ['name' => 'Hijack'])->assertForbidden();
    $this->getJson("/api/v1/teams/{$teamB->id}/projects/{$projectB->id}")->assertForbidden();
    $this->patchJson("/api/v1/teams/{$teamB->id}/projects/{$projectB->id}", ['name' => 'Hijack'])->assertForbidden();
    $this->deleteJson("/api/v1/teams/{$teamB->id}/projects/{$projectB->id}")->assertForbidden();

    // The policy denies independently of the middleware: the owner
    // short-circuit is scoped to the resource's own team.
    expect(Gate::forUser($ownerA)->allows('view', $projectB))->toBeFalse()
        ->and(Gate::forUser($ownerA)->allows('update', $projectB))->toBeFalse()
        ->and(Gate::forUser($ownerA)->allows('delete', $projectB))->toBeFalse();
});

it('never leaves spatie global team id mutated after a project authorization', function () {
    [, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $projectB = Project::factory()->for($teamB)->create();

    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId(null);

    Gate::forUser($ownerA)->allows('update', $projectB);

    expect($registrar->getPermissionsTeamId())->toBeNull();
});

it('lists projects in a bounded number of queries', function () {
    [$team, $owner] = makeTeamWithOwner();
    Project::factory()->for($team)->count(10)->create();
    Sanctum::actingAs($owner);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson("/api/v1/teams/{$team->id}/projects")
        ->assertOk()
        ->assertJsonCount(10, 'data');

    // Guards the index against N+1 regressions: with 10 rows, a per-row
    // query would push this well past the budget.
    expect($queries)->toBeLessThanOrEqual(12);
});
