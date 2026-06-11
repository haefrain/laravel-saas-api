<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Team;
use App\Tenancy\TeamContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behaviour for tenant-owned models. Actions remain the primary source
 * of team_id (derived from the gated route team or the parent resource, never
 * from request input); the creating hook is only a safety net that fills a
 * missing team_id from the bound tenant context.
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('team_id') === null && app()->bound(TeamContext::class)) {
                $model->setAttribute('team_id', app(TeamContext::class)->team->getKey());
            }
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
