<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Tenancy\TeamContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Third layer of cross-tenant defence (after scoped route bindings and the
 * two-gate policies): while a tenant context is bound, every query on a
 * tenant-owned model is filtered to that team, so even a hand-written query
 * with no where() cannot leak rows across teams.
 *
 * Outside an HTTP tenant request (CLI, queue workers, tests) no context is
 * bound and the scope is a no-op.
 *
 * @implements Scope<Model>
 */
class TeamScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound(TeamContext::class)) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('team_id'),
            app(TeamContext::class)->team->getKey(),
        );
    }
}
