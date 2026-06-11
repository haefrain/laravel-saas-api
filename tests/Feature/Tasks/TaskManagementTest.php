<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

function makeProjectInTeam($team): Project
{
    return Project::factory()->for($team)->create();
}

it('lets a member create a task with server-set authorship and tenant', function () {
    [$team] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->postJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks", [
        'title' => 'Ship the billing fix',
        'description' => 'Customers are double-charged on retry.',
        'priority' => 3,
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Ship the billing fix')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.team_id', $team->id)
        ->assertJsonPath('data.project_id', $project->id)
        ->assertJsonPath('data.creator.id', $member->id);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Ship the billing fix',
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $member->id,
    ]);
});

it('validates the create payload', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks", [
        'priority' => 9,
        'due_at' => '2020-01-01',
        'status' => 'done',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'priority', 'due_at', 'status']);
});

it('rejects status and assignee changes through PATCH', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}", [
        'status' => 'done',
        'assignee_id' => $owner->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'assignee_id']);
});

it('lets a member update task fields', function () {
    [$team] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->patchJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Renamed',
        'priority' => 4,
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Renamed')
        ->assertJsonPath('data.priority', 4);
});

it('lets a member delete only their own tasks', function () {
    [$team] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $member = addTeamMember($team, TeamRole::Member);
    $own = Task::factory()->forProject($project)->create(['created_by' => $member->id]);
    $foreign = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($member);

    $this->deleteJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$foreign->id}")
        ->assertForbidden();

    $this->deleteJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$own->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $own->id]);
});

it('lets an admin delete any task in the team', function () {
    [$team] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $task = Task::factory()->forProject($project)->create();
    $admin = addTeamMember($team, TeamRole::Admin);
    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}")
        ->assertNoContent();
});

it('filters tasks by status csv, assignee and search', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $member = addTeamMember($team, TeamRole::Member);

    $todo = Task::factory()->forProject($project)->create(['title' => 'Write spec']);
    $doing = Task::factory()->forProject($project)->status(TaskStatus::InProgress)
        ->create(['assignee_id' => $owner->id]);
    Task::factory()->forProject($project)->status(TaskStatus::Done)->create();

    Sanctum::actingAs($owner);
    $base = "/api/v1/teams/{$team->id}/projects/{$project->id}/tasks";

    $this->getJson("{$base}?status=todo,in_progress")->assertOk()->assertJsonCount(2, 'data');
    $this->getJson("{$base}?assignee_id=me")->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $doing->id);
    $this->getJson("{$base}?assignee_id=null")->assertOk()->assertJsonCount(2, 'data');
    $this->getJson("{$base}?q=spec")->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $todo->id);
    $this->getJson("{$base}?status=bogus")->assertUnprocessable();
});

it('sorts tasks by priority descending', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    Task::factory()->forProject($project)->create(['priority' => 1]);
    $urgent = Task::factory()->forProject($project)->create(['priority' => 4]);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks?sort=-priority")
        ->assertOk()
        ->assertJsonPath('data.0.id', $urgent->id);
});

it('shows a task with counts and relations', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = makeProjectInTeam($team);
    $task = Task::factory()->forProject($project)->create(['assignee_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonPath('data.assignee.id', $owner->id)
        ->assertJsonPath('data.comments_count', 0);
});
