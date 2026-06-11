<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

it('shows only the caller\'s own notifications, even to the team owner', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->forProject($project)->create();
    $member = addTeamMember($team, TeamRole::Member);

    // Owner assigns the member → notification for the member only.
    Sanctum::actingAs($owner);
    $this->putJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}/assignee", [
        'assignee_id' => $member->id,
    ])->assertOk();

    // The owner's feed is empty: no team-role override on notifications.
    $this->getJson("/api/v1/teams/{$team->id}/notifications")
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Sanctum::actingAs($member);
    $this->getJson("/api/v1/teams/{$team->id}/notifications")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'task_assigned')
        ->assertJsonPath('data.0.data.actor_id', $owner->id);
});

it('notifies the assignee on a status change with from and to', function () {
    [$team, $owner] = makeTeamWithOwner();
    $project = Project::factory()->for($team)->create();
    $member = addTeamMember($team, TeamRole::Member);
    $task = Task::factory()->forProject($project)->create(['assignee_id' => $member->id]);
    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/teams/{$team->id}/projects/{$project->id}/tasks/{$task->id}/transition", [
        'status' => 'in_progress',
    ])->assertOk();

    $notification = Notification::query()
        ->where('user_id', $member->id)
        ->where('type', 'task_status_changed')
        ->firstOrFail();

    expect($notification->data)->toMatchArray([
        'actor_id' => $owner->id,
        'from' => 'todo',
        'to' => 'in_progress',
    ]);
});

it('filters unread notifications', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    $read = Notification::factory()->create(['team_id' => $team->id, 'user_id' => $member->id]);
    $read->markAsRead();
    $unread = Notification::factory()->create(['team_id' => $team->id, 'user_id' => $member->id]);
    Sanctum::actingAs($member);

    $this->getJson("/api/v1/teams/{$team->id}/notifications?unread=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $unread->id);
});

it('marks own notifications as read, idempotently', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    $notification = Notification::factory()->create(['team_id' => $team->id, 'user_id' => $member->id]);
    Sanctum::actingAs($member);

    $url = "/api/v1/teams/{$team->id}/notifications/{$notification->id}/read";

    $this->patchJson($url)->assertOk();
    $readAt = $notification->refresh()->read_at;
    expect($readAt)->not->toBeNull();

    $this->patchJson($url)->assertOk();
    expect($notification->refresh()->read_at?->toIso8601String())
        ->toBe($readAt?->toIso8601String());
});

it('forbids marking a teammate\'s notification as read (IDOR)', function () {
    [$team, $owner] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    $notification = Notification::factory()->create(['team_id' => $team->id, 'user_id' => $member->id]);

    // Even the team owner cannot touch someone else's notification.
    Sanctum::actingAs($owner);
    $this->patchJson("/api/v1/teams/{$team->id}/notifications/{$notification->id}/read")
        ->assertForbidden();

    expect($notification->refresh()->read_at)->toBeNull();
});

it('returns 404 for a notification from another team', function () {
    [$teamA, $ownerA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    $memberB = addTeamMember($teamB, TeamRole::Member);
    $foreign = Notification::factory()->create(['team_id' => $teamB->id, 'user_id' => $memberB->id]);
    Sanctum::actingAs($ownerA);

    $this->patchJson("/api/v1/teams/{$teamA->id}/notifications/{$foreign->id}/read")
        ->assertNotFound();
});
