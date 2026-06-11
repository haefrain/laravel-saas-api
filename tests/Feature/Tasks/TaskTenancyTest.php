<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Tenancy\TeamContext;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('returns 404 for another team\'s task under your own project path', function () {
    [$teamA, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $projectA = Project::factory()->for($teamA)->create();
    $projectB = Project::factory()->for($teamB)->create();
    $taskB = Task::factory()->forProject($projectB)->create();
    Sanctum::actingAs($ownerA);

    $this->getJson("/api/v1/teams/{$teamA->id}/projects/{$projectA->id}/tasks/{$taskB->id}")
        ->assertNotFound();
});

it('denies the owner of team A every task action on team B', function () {
    [, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $projectB = Project::factory()->for($teamB)->create();
    $taskB = Task::factory()->forProject($projectB)->create();
    Sanctum::actingAs($ownerA);

    $base = "/api/v1/teams/{$teamB->id}/projects/{$projectB->id}/tasks";
    $this->getJson($base)->assertForbidden();
    $this->postJson($base, ['title' => 'Hijack'])->assertForbidden();
    $this->getJson("{$base}/{$taskB->id}")->assertForbidden();
    $this->postJson("{$base}/{$taskB->id}/transition", ['status' => 'in_progress'])->assertForbidden();
});

it('always derives task team_id from the parent project', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    Sanctum::actingAs($owner);

    $id = $this->postJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks", [
        'title' => 'Invariant check',
    ])->assertCreated()->json('data.id');

    // The invariant the denormalization depends on: no drift from the parent.
    $drifted = DB::table('tasks')
        ->join('projects', 'projects.id', '=', 'tasks.project_id')
        ->whereColumn('tasks.team_id', '!=', 'projects.team_id')
        ->count();

    expect($drifted)->toBe(0)
        ->and(Task::query()->findOrFail($id)->team_id)->toBe($project->team_id);
});

it('scopes every task query to the bound tenant context', function () {
    [$teamA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    Task::factory()->forProject(Project::factory()->for($teamA)->create())->count(2)->create();
    Task::factory()->forProject(Project::factory()->for($teamB)->create())->create();

    app()->instance(TeamContext::class, new TeamContext($teamA));

    try {
        expect(Task::query()->get())->toHaveCount(2);
    } finally {
        app()->forgetInstance(TeamContext::class);
    }
});

it('lists tasks in a bounded number of queries', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    Task::factory()->forProject($project)->count(10)->create(['assignee_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks")
        ->assertOk()
        ->assertJsonCount(10, 'data');

    // Eager assignee/creator + comments_count: a per-row lookup with 10 rows
    // would blow well past this budget.
    expect($queries)->toBeLessThanOrEqual(15);
});
