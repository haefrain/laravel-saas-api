# ADR 0001 — Tenancy via the `/teams/{team}` route segment

## Status

Accepted

## Context

Every tenant-owned resource (projects, tasks, comments, notifications) must be
unambiguously scoped to one team. Common options: a subdomain per tenant, an
`X-Team-Id` header, a "current team" stored on the user, or a route segment.

## Decision

The active team is the `{team}` route parameter: `/api/v1/teams/{team}/...`.
A dedicated `tenant` middleware (`ResolveTeamContext`) resolves the binding,
rejects non-members with `403 team_forbidden` before any controller runs,
sets spatie's per-request team id, and binds a `TeamContext` value object for
downstream code.

## Consequences

- The tenant is explicit in every URL — visible in logs, reproducible in curl,
  and impossible to "forget" the way an implicit current-team default can be.
- `Route::scopeBindings()` gives router-level 404s for cross-tenant child ids
  for free, because nested bindings resolve through the parent relation.
- A user can act on two teams in parallel requests without server-side state.
- Trade-off: URLs are longer, and a client must always know its team id. For
  a SPA/mobile client that already lists the user's teams, this is a non-issue.
