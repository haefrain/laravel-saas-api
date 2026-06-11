<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use App\Tenancy\TeamContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the `{team}` route binding into the tenant context for this request:
 *  1. membership gate (a non-member of the team is forbidden before any controller),
 *  2. sets spatie's active team id for the controller/action leg,
 *  3. binds TeamContext so downstream code reads the team without re-resolving.
 *
 * Policies still re-derive the team from the resource and save/restore spatie's
 * global id, so this middleware is one layer of defence, never the only one.
 */
class ResolveTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('team');
        if (! $team instanceof Team) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();

        abort_unless($user->belongsToTeam($team), 403, 'team_forbidden');

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        app()->instance(TeamContext::class, new TeamContext($team));
        $request->attributes->set('team', $team);

        return $next($request);
    }
}
