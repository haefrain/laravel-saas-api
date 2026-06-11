# ADR 0004 — Custom tenant-scoped notifications table

## Status

Accepted

## Context

Laravel ships a `notifications` table (UUID morphs, single `notifiable`).
Ours must be tenant-scoped (a notification belongs to a team context), be
queryable per team, and enforce object-level ownership: an owner/admin must
NOT be able to read a teammate's notifications — team role is irrelevant here.

## Decision

A custom `notifications` model: integer keys, `team_id` (under the global
`TeamScope`), `user_id` recipient, optional `task_id`, `type` + JSON `data`,
`read_at`. Object-level authorization is a dedicated `NotificationPolicy`
(`user_id === auth id`) plus a hard `where user_id` filter on the index — the
tenant scope alone would still expose teammates' rows. Rows are produced only
by `CreateNotificationAction`, invoked from queued listeners
(`ShouldQueue` + `afterCommit`) reacting to `TaskAssigned` /
`TaskStatusChanged` domain events that carry ids, not models.

## Consequences

- The IDOR class of bug ("mark someone else's notification as read") is
  policy-denied and regression-tested, including for the team owner.
- `afterCommit` listeners mean a rolled-back assignment never notifies; the
  sync queue driver in CI executes them inline so tests assert real rows.
- Trade-off: we forgo Laravel's notification channels (mail, broadcast). If
  needed later, a channel adapter can fan out from the same action.
