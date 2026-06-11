<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

function transitionUrl($team, $project, $task): string
{
    return "/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}/transition";
}

it('walks the happy path and stamps completed_at on done', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    foreach (['in_progress', 'in_review'] as $status) {
        $this->postJson(transitionUrl($team, $project, $task), ['status' => $status])
            ->assertOk()
            ->assertJsonPath('data.status', $status)
            ->assertJsonPath('data.completed_at', null);
    }

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');

    expect($task->refresh()->completed_at)->not->toBeNull();
});

it('clears completed_at when a done task is reopened', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->status(TaskStatus::Done)->create();
    $task->forceFill(['completed_at' => now()])->save();
    Sanctum::actingAs($owner);

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.completed_at', null);

    expect($task->refresh()->completed_at)->toBeNull();
});

it('rejects the todo to done skip with the allowed edges', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'done'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($task->refresh()->status)->toBe(TaskStatus::Todo);
});

it('revives a cancelled task back to todo', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->status(TaskStatus::Cancelled)->create();
    Sanctum::actingAs($owner);

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'todo'])
        ->assertOk()
        ->assertJsonPath('data.status', 'todo');
});

it('rejects a self-transition', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'todo'])
        ->assertUnprocessable();
});

it('rejects an unknown lifecycle value', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->postJson(transitionUrl($team, $project, $task), ['status' => 'archived'])
        ->assertUnprocessable();
});
