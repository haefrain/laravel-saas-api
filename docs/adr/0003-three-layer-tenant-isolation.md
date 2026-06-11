# ADR 0003 — Three-layer cross-tenant isolation with denormalized `team_id`

## Status

Accepted

## Context

Cross-tenant leakage is the defining failure mode of a multi-tenant API. A
single guard (just a global scope, or just policies) fails silently the day
someone adds a flat route, a console command, or a hand-written query.

## Decision

Three independent layers, all always on:

1. **Router** — `Route::scopeBindings()`: nested `{project}`/`{task}`/
   `{comment}`/`{notification}` ids resolve through the parent relation; a
   foreign id is a 404 before any code runs.
2. **Policies** — every policy re-derives the team **from the resource itself**
   (never from the route) and answers both gates: membership of that team AND
   team-scoped permission. The owner short-circuit lives inside each method,
   scoped to the resource's team — deliberately **not** `Gate::before`, which
   would let the owner of team A act on team B.
3. **Query scope** — a global `TeamScope` filters every tenant-model query to
   the bound `TeamContext`; a where-less query cannot leak. It is a no-op in
   CLI/queue contexts where no context is bound.

Child rows carry a denormalized `team_id` **derived from the parent row**
(`task.team_id = project.team_id`) inside the create actions — never from the
tenant context — so the column cannot drift even if a future code path runs
without middleware. A test asserts the invariant by joining child to parent.

## Consequences

- Any one layer can be bypassed by future code without opening a hole.
- The headline test suite encodes the contract: owner of team A is denied
  every action on team B (404 nested / 403 policy), spatie's global team id
  never leaks, and tenant indexes stay within a fixed query budget.
- Cost: one extra FK column per child table and a discipline (actions derive
  `team_id` from parents) that must be followed — enforced by tests.
