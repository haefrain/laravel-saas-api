<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Team $team */
        $team = $this->resource;
        $user = $request->user();
        // Caller's role is read from the per-request membership map, so many
        // teams can be serialized in one response each showing its own role.
        $role = $user instanceof User ? $user->roleForTeam($team) : null;

        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'role' => $role?->value,
            'members_count' => $this->whenCounted('members'),
            'created_at' => $team->created_at?->toIso8601String(),
        ];
    }
}
