# Admin Panel UI Contract

## Scope

This is an internal, authenticated web interface. The public Blade routes remain unchanged. The panel is available at `/admin` after successful administrator authentication.

## Access contract

| Actor | `/admin` and model resource | Model actions |
|---|---|---|
| Unauthenticated visitor | Redirected to authentication | Not available |
| Authenticated non-admin | Forbidden / denied | Not available |
| Authenticated admin with verified email | Allowed | Allowed according to record state |
| Authenticated admin without verified email | Denied | Not available |

## Models list

The list must expose these columns:

- public name;
- email;
- WhatsApp;
- location;
- registration date;
- email verification state;
- review state: pending, approved, rejected;
- publication state: published, unpublished.

The list supports text search across public name, email, WhatsApp and location. It supports filters for email verification, review state and publication state. Pagination must preserve the current search and filters.

## Model detail

The detail view shows account identity, profile data, registration timestamp, email verification timestamp/state, review state and publication state. Passwords, reset tokens and verification tokens are never shown.

## Actions

| Action | Preconditions | Result |
|---|---|---|
| Approve | Admin; profile pending or rejected | Review state becomes approved; publication unchanged. |
| Reject | Admin; profile pending or approved | Review state becomes rejected; publication becomes false. |
| Publish | Admin; review state approved | Publication becomes true. |
| Unpublish | Admin; publication true | Publication becomes false; review state unchanged. |
| Edit basic data | Admin; valid name, WhatsApp, location | Profile data updates; email verification and publication state unchanged. |

Every state-changing action requires confirmation and displays a success or failure notification. Invalid or unauthorized actions must not mutate the record.

## Public compatibility

The contract does not add or replace public Blade routes. Existing registration, login, email verification, password recovery and account privacy behavior remains outside the panel and must continue to pass its existing tests.
