<?php

declare(strict_types=1);

use App\Actions\Tasks\AssignTaskAction;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

function assigneeUrl($team, $project, $task): string
{
    return "/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}/assignee";
}

it('assigns a team member and notifies them', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => $member->id])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $member->id);

    // Queue runs sync in tests: the queued listener already executed.
    $this->assertDatabaseHas('notifications', [
        'team_id' => $team->id,
        'user_id' => $member->id,
        'task_id' => $task->id,
        'type' => 'task_assigned',
    ]);
});

it('does not notify on self-assignment', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => $owner->id])
        ->assertOk();

    $this->assertDatabaseCount('notifications', 0);
});

it('does not duplicate the notification when re-assigning the same member', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => $member->id])->assertOk();
    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => $member->id])->assertOk();

    $this->assertDatabaseCount('notifications', 1);
});

it('unassigns with an explicit null and stays silent', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    $task = Task::factory()->forProject($project)->create(['assignee_id' => $member->id]);
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => null])
        ->assertOk()
        ->assertJsonPath('data.assignee', null);

    $this->assertDatabaseCount('notifications', 0);
});

it('rejects an assignee from another team', function () {
    [$team, $owner] = makeTeamWithOwner();
    [$teamB] = makeTeamWithOwner('Other');
    $stranger = addTeamMember($teamB, TeamRole::Member);
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => $stranger->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assignee_id']);
});

it('rejects a non-existent assignee', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->putJson(assigneeUrl($team, $project, $task), ['assignee_id' => 999999])
        ->assertUnprocessable();
});

it('re-asserts team membership inside the action itself', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $outsider = User::factory()->create();

    // Bypass the FormRequest on purpose: the action must still refuse.
    app(AssignTaskAction::class)->handle($task, $outsider->id, $owner);
})->throws(ValidationException::class);

it('notifies the assignee when creating a task already assigned', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks", [
        'title' => 'Assigned at birth',
        'assignee_id' => $member->id,
    ])->assertCreated();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $member->id,
        'type' => 'task_assigned',
    ]);
});
