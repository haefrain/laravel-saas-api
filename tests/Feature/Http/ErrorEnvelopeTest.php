<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

it('wraps 401 in the error envelope', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('wraps policy denials as forbidden', function () {
    [$team] = makeTeamWithOwner();
    $member = addTeamMember($team, TeamRole::Member);
    Sanctum::actingAs($member);

    // Members lack team.delete: passes the membership gate, fails the policy.
    $this->deleteJson("/api/v1/teams/{$team->id}")
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

it('keeps the team_forbidden code for the membership gate', function () {
    [$teamA] = makeTeamWithOwner('Team A');
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/teams/{$teamA->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'team_forbidden')
        ->assertJsonPath('error.code', 'team_forbidden');
});

it('wraps 404 without echoing the requested id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $missingId = (int) Team::max('id') + 999;
    $response = $this->getJson("/api/v1/teams/{$missingId}")
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');

    expect($response->getContent())->not->toContain((string) $missingId);
});

it('wraps 405 with the allowed methods', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/teams')
        ->assertStatus(405)
        ->assertJsonPath('error.code', 'method_not_allowed');
});

it('keeps top-level validation errors and adds the envelope code', function () {
    $this->postJson('/api/v1/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password'])
        ->assertJsonPath('error.code', 'validation_error');
});

it('suppresses internals on production-mode 500s', function () {
    config(['app.debug' => false]);

    Route::middleware('api')->get('/api/v1/_boom', function (): never {
        throw new RuntimeException('sensitive database credentials leaked');
    });

    $response = $this->getJson('/api/v1/_boom')
        ->assertStatus(500)
        ->assertJsonPath('error.code', 'server_error');

    expect($response->getContent())
        ->not->toContain('sensitive')
        ->and($response->json('trace_id'))->not->toBeNull();
});
