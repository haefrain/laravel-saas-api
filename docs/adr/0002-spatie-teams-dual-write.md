# ADR 0002 — spatie/laravel-permission teams mode + `membership_role` dual-write

## Status

Accepted

## Context

Roles are per-team (the same user is owner of team A and member of team B).
spatie/laravel-permission supports this with `'teams' => true`, but its
"active team" is a **process-global** registrar value — dangerous if it leaks
between two authorization checks for different teams in one request. We also
need a cheap way to know "which teams does this user belong to, with which
role" without N spatie lookups.

## Decision

- spatie teams mode is the **authorization source of truth** (permissions per
  role per team, `guard_name='web'` pinned explicitly on every role and
  permission row).
- The `team_user` pivot carries a denormalized `membership_role` column,
  **dual-written in the same transaction** by the membership actions, used for
  fast read paths (`User::membershipMap()` loads team→role once per request).
- Every spatie read inside policies saves and restores the registrar's global
  team id (`try/finally`), so no authorization check ever leaves global state
  mutated. Regression-tested.

## Consequences

- Read paths (resources, `belongsToTeam`, role display) cost one query per
  request instead of one per check; the spatie tables stay authoritative for
  `can()`.
- The two sources can theoretically diverge — prevented by writing both only
  inside actions, in one transaction, from one server-derived `TeamRole` value
  (never request input), and asserted in tests.
- The save/restore discipline makes policies safe to call in any order, e.g.
  serializing many teams (each with its own role) in a single response.
