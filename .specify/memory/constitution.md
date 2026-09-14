<!--
Sync Impact Report
- Version change: 1.0.0 -> 1.1.0
- Modified principles: all five principles materially expanded with the requested technical rules
- Added sections: explicit Laravel/MySQL stack constraints; privacy and moderation requirements
- Removed sections: none
- Follow-up TODOs: confirm the original ratification date
-->

# Divas Cuyo Constitution

## Core Principles

### I. Laravel Standards and Simplicity

The project MUST use Laravel conventions, PSR-12 formatting, Eloquent ORM, and the configured
MySQL database. Technical names for classes, methods, variables, routes, database objects, and
other implementation identifiers MUST be in English. User-visible text MUST be in Spanish.
Controllers MUST remain thin: they coordinate the request lifecycle and delegate domain work
to models, Services, or Actions. Complex business logic MUST be extracted to a Service or
Action when it cannot remain clear and cohesive in the controller or model. External packages
MUST NOT be introduced without a concrete requirement and documented maintenance value.
These rules preserve consistency with Laravel and reduce unnecessary complexity.

### II. Explicit and Safe Persistence

Every database schema change MUST be implemented through a Laravel migration; manual or
out-of-band schema changes MUST NOT be used as the delivery mechanism. Application persistence
MUST use Eloquent ORM unless a documented performance or capability constraint requires a
lower-level query. Queries MUST be reviewed for N+1 behavior and MUST use eager loading,
appropriate aggregation, or another measured solution when relationships are accessed in
collections. This makes schema history reproducible and protects runtime performance.

### III. Validated and Authorized Boundaries

Request validation MUST be implemented with Laravel Form Requests for endpoint input. Access
control MUST be implemented with Policies or Gates and MUST be enforced before protected data
or actions are used. Controllers MUST NOT duplicate validation or authorization rules that
belong in these mechanisms. Error responses MUST NOT expose secrets or unnecessary internal
details. Centralizing these boundaries makes security behavior explicit and testable.

### IV. Privacy and Moderated Publication

Identity documentation and all private data MUST receive heightened protection through
least-privilege authorization, secure handling, and omission from unauthorized responses and
logs. A model MUST NOT access the private information of another model unless an explicit,
authorized application rule permits that access. Any change subject to moderation MUST remain
unpublished and unavailable through public presentation paths until an authorized moderator
approves it. These restrictions protect people represented in the system and preserve trust in
the publication workflow.

### V. Tested and Maintainable Delivery

Changes to critical business rules MUST include automated tests that cover the rule and its
relevant authorization or publication states. Feature tests MUST cover user-visible HTTP
behavior and integration boundaries; unit tests SHOULD cover isolated domain logic. Before
merging, the relevant test suite MUST pass, and important failures or business events MUST be
diagnosable through Laravel exceptions and appropriate logs. This provides regression
protection and supports safe maintenance.

## Project Constraints

The application MUST remain compatible with the versions declared by `composer.json`,
`package.json`, and the configured MySQL environment. Server-rendered Laravel views, Laravel
routing, Eloquent, Form Requests, Policies or Gates, migrations, and the configured asset
pipeline are the default implementation paths. A new package, persistence technology, or
frontend architecture requires a documented reason, an impact assessment, and tests covering
the integration boundary.

## Development Workflow

Each change MUST have a reviewable scope and MUST preserve existing behavior unless the change
explicitly revises that behavior. Before merging, contributors MUST run the relevant automated
tests and MUST review PSR-12 conformance, validation, authorization, migrations, N+1 risk,
privacy, moderation, logging, and configuration impact. Schema changes MUST be reversible
where practical and MUST include migration-safe rollout notes when they affect existing data.
Reviewers MUST reject changes that violate a principle unless the exception and its trade-offs
are documented and approved.

## Governance

This constitution is the highest-level project guidance and supersedes conflicting local
practice. Amendments MUST be proposed as a change to this file, explain the motivation and
impact, and receive project-owner approval before being merged. Any amendment that changes
implementation expectations MUST identify affected documentation, tests, or migration work.

The constitution follows semantic versioning: MAJOR increments represent backward-incompatible
principle removals or redefinitions; MINOR increments represent new principles or materially
expanded governance; PATCH increments represent clarifications and non-semantic refinements.

Every feature review MUST check compliance with these principles. Exceptions MUST be explicit,
time-bounded where possible, and recorded with their rationale. The constitution MUST be
reviewed whenever the framework baseline, deployment model, security posture, or development
workflow materially changes.

**Version**: 1.1.0 | **Ratified**: TODO(RATIFICATION_DATE): confirm original adoption date | **Last Amended**: 2026-09-14
