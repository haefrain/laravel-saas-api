<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Scopes\TeamScope;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

class CreateProjectAction
{
    /**
     * @param  array<string, mixed>  $attributes  validated Store payload (name/description/status only)
     */
    public function handle(Team $team, User $creator, array $attributes): Project
    {
        $name = (string) $attributes['name'];

        return Project::create([
            // Tenant and authorship are server-derived — the gated route team
            // and the authenticated caller — never request input.
            'team_id' => $team->getKey(),
            'created_by' => $creator->getKey(),
            'name' => $name,
            'slug' => $this->uniqueSlug($team, $name),
            'description' => isset($attributes['description']) ? (string) $attributes['description'] : null,
            'status' => isset($attributes['status'])
                ? ProjectStatus::from((string) $attributes['status'])
                : ProjectStatus::Active,
        ]);
    }

    private function uniqueSlug(Team $team, string $name): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'project' : $base;

        $slug = $base;
        while ($this->slugTaken($team, $slug)) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    private function slugTaken(Team $team, string $slug): bool
    {
        // The unique index spans soft-deleted rows too, so the probe must
        // bypass both the tenant scope and SoftDeletes.
        return Project::withoutGlobalScope(TeamScope::class)
            ->withTrashed()
            ->where('team_id', $team->getKey())
            ->where('slug', $slug)
            ->exists();
    }
}
