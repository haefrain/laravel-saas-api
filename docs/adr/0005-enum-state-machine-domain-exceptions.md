# ADR 0005 — Task lifecycle as an enum state machine + domain exceptions

## Status

Accepted

## Context

Task status transitions have business rules (work cannot skip review;
done/cancelled are terminal except explicit reopen/revive). Scattering these
in controllers or requests makes them unenforceable; a workflow library is
overkill for five states.

## Decision

`TaskStatus` is a backed enum owning its transition graph
(`allowedTransitions()`, `canTransitionTo()`, `isTerminal()`), unit-tested
cell by cell against the full 5×5 matrix. Status changes flow exclusively
through `POST .../transition` → `TransitionTaskStatusAction`, which validates
the edge, stamps/clears `completed_at` (mass-assignment-guarded), and fires
`TaskStatusChanged`. `PATCH` requests explicitly prohibit `status` and
`assignee_id`. Illegal edges throw `InvalidTaskTransitionException`, a
`DomainException` subclass carrying its own HTTP status, machine code and
details — one render closure maps every domain rule to the API error envelope
(`422 invalid_transition` with `{ from, to, allowed_transitions }`), so
business code never sets HTTP codes.

## Consequences

- The lifecycle is one readable function; adding a state forces the compiler
  (match expression) and the matrix test to acknowledge every new edge.
- Clients get machine-usable violations (the allowed edges) instead of prose.
- The same `DomainException` base is ready for future invariants (e.g.
  last-owner protection) without touching the exception handler again.
