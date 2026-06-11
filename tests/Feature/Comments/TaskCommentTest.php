<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Laravel\Sanctum\Sanctum;

function commentsUrl($team, $project, $task): string
{
    return "/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}/comments";
}

it('lets a member comment with server-set authorship', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    $this->postJson(commentsUrl($team, $project, $task), [
        'body' => 'On it.',
        'user_id' => 999999, // spoof attempt — not part of the validated payload
    ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'On it.')
        ->assertJsonPath('data.author.id', $member->id);

    $this->assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'team_id' => $team->id,
        'user_id' => $member->id,
    ]);
});

it('lists comments chronologically with their authors', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    TaskComment::factory()->forTask($task)->count(3)->create();
    Sanctum::actingAs($owner);

    $this->getJson(commentsUrl($team, $project, $task))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3);
});

it('requires a body', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    Sanctum::actingAs($owner);

    $this->postJson(commentsUrl($team, $project, $task), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

it('lets a member delete only their own comments', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);
    $own = TaskComment::factory()->forTask($task)->create(['user_id' => $member->id]);
    $foreign = TaskComment::factory()->forTask($task)->create();
    Sanctum::actingAs($member);

    $this->deleteJson(commentsUrl($team, $project, $task)."/{$foreign->id}")
        ->assertForbidden();

    $this->deleteJson(commentsUrl($team, $project, $task)."/{$own->id}")
        ->assertNoContent();
});

it('lets an admin delete any comment', function () {
    [$team] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $comment = TaskComment::factory()->forTask($task)->create();
    $admin = addTeamMember($team, TeamRole::Admin);
    Sanctum::actingAs($admin);

    $this->deleteJson(commentsUrl($team, $project, $task)."/{$comment->id}")
        ->assertNoContent();
});

it('returns 404 for a comment that belongs to another task', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $otherTask = Task::factory()->forProject($project)->create();
    $strayComment = TaskComment::factory()->forTask($otherTask)->create();
    Sanctum::actingAs($owner);

    $this->deleteJson(commentsUrl($team, $project, $task)."/{$strayComment->id}")
        ->assertNotFound();
});
