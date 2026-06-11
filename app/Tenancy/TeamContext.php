<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Team;

/**
 * The active team for the current request, resolved once by the
 * ResolveTeamContext middleware and injected where Actions/Resources/scopes
 * need it without re-resolving from the route.
 */
final readonly class TeamContext
{
    public function __construct(public Team $team) {}
}
