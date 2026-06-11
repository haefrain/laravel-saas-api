<?php

declare(strict_types=1);

use App\Models\Project;
use App\Tenancy\TeamContext;

it('scopes every project query to the bound tenant context', function () {
    [$teamA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    Project::factory()->for($teamA)->count(2)->create();
    Project::factory()->for($teamB)->create();

    // Simulate the tenant middleware having bound team A as the active context.
    app()->instance(TeamContext::class, new TeamContext($teamA));

    try {
        // Deliberately no where(): the global TeamScope must filter on its own.
        $projects = Project::query()->get();

        expect($projects)->toHaveCount(2)
            ->and($projects->pluck('team_id')->unique()->all())->toBe([$teamA->id]);
    } finally {
        app()->forgetInstance(TeamContext::class);
    }
});

it('leaves project queries unscoped when no tenant context is bound', function () {
    [$teamA] = makeTeamWithOwner('Team A');
    [$teamB] = makeTeamWithOwner('Team B');
    Project::factory()->for($teamA)->create();
    Project::factory()->for($teamB)->create();

    // CLI / queue / test code without an HTTP tenant context sees everything.
    expect(Project::query()->count())->toBe(2);
});

it('auto-fills team_id from the tenant context when omitted on create', function () {
    [$teamA, $owner] = makeTeamWithOwner('Team A');

    app()->instance(TeamContext::class, new TeamContext($teamA));

    try {
        $project = Project::create([
            'created_by' => $owner->id,
            'name' => 'Context-filled',
            'slug' => 'context-filled',
        ]);

        expect($project->team_id)->toBe($teamA->id);
    } finally {
        app()->forgetInstance(TeamContext::class);
    }
});
