<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;

it('lets the owner create a project', function () {
    [$team, $owner] = makeTeamWithOwner();
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects", [
        'name' => 'Apollo',
        'description' => 'Launch sequence rewrite',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Apollo')
        ->assertJsonPath('data.team_id', $team->id)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('projects', [
        'team_id' => $team->id,
        'name' => 'Apollo',
        'created_by' => $owner->id,
    ]);
});

it('lets an admin create a project', function () {
    [$team] = makeTeamWithOwner();
    $admin = addTeamMember($team, TeamRole::Admin);
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/teams/{$team->id}/projects", ['name' => 'Borealis'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Borealis');
});

it('forbids a member from creating a project', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->postJson("/api/v1/teams/{$team->id}/projects", ['name' => 'Nope'])
        ->assertForbidden();
});

it('ignores spoofed team_id and created_by in the payload', function () {
    [$team, $owner] = makeTeamWithOwner();
    [$teamB] = makeTeamWithOwner('Other');
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects", [
        'name' => 'Spoof attempt',
        'team_id' => $teamB->id,
        'created_by' => 999999,
    ])->assertCreated();

    // Both fields are server-derived; unvalidated input never reaches the model.
    $this->assertDatabaseHas('projects', [
        'name' => 'Spoof attempt',
        'team_id' => $team->id,
        'created_by' => $owner->id,
    ]);
});

it('generates unique slugs per team for duplicate names', function () {
    [$team, $owner] = makeTeamWithOwner();
    Sanctum::actingAs($owner);

    $first = $this->postJson("/api/v1/teams/{$team->id}/projects", ['name' => 'Same Name'])
        ->assertCreated()->json('data.slug');
    $second = $this->postJson("/api/v1/teams/{$team->id}/projects", ['name' => 'Same Name'])
        ->assertCreated()->json('data.slug');

    expect($first)->toBe('same-name')
        ->and($second)->not->toBe($first)
        ->and($second)->toStartWith('same-name-');
});

it('validates the payload on create', function () {
    [$team, $owner] = makeTeamWithOwner();
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects", ['status' => 'bogus'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'status']);
});

it('lists projects with pagination meta for a member', function () {
    [$team] = makeTeamWithOwner();
    Project::factory()->for($team)->count(3)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->getJson("/api/v1/teams/{$team->id}/projects")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3);
});

it('filters projects by status', function () {
    [$team, $owner] = makeTeamWithOwner();
    Project::factory()->for($team)->count(2)->create();
    $archived = Project::factory()->for($team)->archived()->create();
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects?status=archived")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $archived->id);
});

it('searches projects by name', function () {
    [$team, $owner] = makeTeamWithOwner();
    Project::factory()->for($team)->create(['name' => 'Billing revamp']);
    Project::factory()->for($team)->create(['name' => 'Mobile app']);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects?q=billing")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Billing revamp');
});

it('sorts projects by name', function () {
    [$team, $owner] = makeTeamWithOwner();
    Project::factory()->for($team)->create(['name' => 'Zeta']);
    Project::factory()->for($team)->create(['name' => 'Alpha']);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects?sort=name")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha');
});

it('rejects an invalid status filter', function () {
    [$team, $owner] = makeTeamWithOwner();
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects?status=bogus")
        ->assertUnprocessable();
});

it('shows a project to a member', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->getJson("/api/v1/teams/{$team->id}/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.slug', $project->slug);
});

it('lets an admin update a project and keeps the slug stable', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create(['name' => 'Old name']);
    $admin = addTeamMember($team, TeamRole::Admin);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/teams/{$team->id}/projects/{$project->id}", [
        'name' => 'New name',
        'status' => 'archived',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New name')
        ->assertJsonPath('data.status', 'archived')
        ->assertJsonPath('data.slug', $project->slug);
});

it('forbids a member from updating a project', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->patchJson("/api/v1/teams/{$team->id}/projects/{$project->id}", ['name' => 'Hijack'])
        ->assertForbidden();
});

it('lets the owner soft-delete a project', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    Sanctum::actingAs($owner);

    $this->deleteJson("/api/v1/teams/{$team->id}/projects/{$project->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

it('forbids a member from deleting a project', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->deleteJson("/api/v1/teams/{$team->id}/projects/{$project->id}")
        ->assertForbidden();
});
