# Quickstart: Panel administrativo de modelos

## Prerequisites

- PHP 8.2+ and Composer dependencies installed.
- MySQL running and configured in `.env`.
- Existing database migrations applied.
- Filament 5 installed and its admin panel provider generated.
- A test user promoted to administrator with verified email.

Promote an existing verified account without exposing an admin-management screen:

```bash
php artisan admin:promote admin@example.com
```

The command refuses unverified accounts and does not create accounts or change passwords.

## Setup

```bash
composer require filament/filament:"^5.0" -W
php artisan filament:install --panels
php artisan migrate
php artisan config:clear
```

The implementation must add the model-profile/admin migrations before running `migrate`. Do not run schema changes manually in MySQL.

## Validation scenarios

1. Open `/admin` unauthenticated; confirm the request goes to authentication and no model data is shown.
2. Authenticate as a regular verified model; confirm panel access, list, detail and model actions are denied.
3. Authenticate as a verified administrator; confirm the model list loads with the columns ordered as name, email, WhatsApp, location, email verification, review, publication and registration date.
4. With no model profiles available, confirm the list shows the empty heading and explanation instead of a blank screen.
5. Create models with different names, emails, WhatsApp values and locations; search by each field and verify only matching records remain. Confirm a search without matches displays the standard no-results state.
6. Filter by unverified/verified email, pending/approved/rejected review and published/unpublished state; combine filters and confirm the results remain consistent while paginating.
7. Open a model detail and verify name, email, WhatsApp, location, registration date, email verification state, review state, publication state and review audit information.
8. From the list or detail, choose `Aprobar` for a pending profile and confirm the modal explains that approval does not publish automatically. Confirm the result badge changes to approved.
9. Choose `Rechazar` for a pending or approved profile and confirm the modal explains that publication will be removed. Confirm the result is rejected and unpublished.
10. Attempt to publish a pending or rejected profile; confirm the action is unavailable and a direct unauthorized request is rejected server-side.
11. Choose `Publicar` for an approved profile, confirm the warning, and then choose `Despublicar`; confirm publication changes while review state remains approved.
12. Choose `Editar` and verify the form contains only name, WhatsApp and location. Save valid and invalid data; confirm validation and preservation of previous values on failure.
13. Confirm email, password, email verification, administrator flag, moderation state, publication state and review audit fields are not editable from the general form.
14. Confirm the panel never displays passwords, tokens or remember-session data, and that all list/detail queries load the user and reviewer relationships without N+1 queries.
15. Run the public registration, login, email verification, password recovery and account privacy regression tests; verify they remain unchanged.

## Automated validation

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan view:cache
php artisan optimize:clear
```

The feature test suite must cover panel authorization, search, filters, detail visibility, edit validation, moderation transitions, publication guards and regression of the public authentication flows. Apply the pending migrations against MySQL before manual validation; do not modify public routes or create an administrator from the panel.

## Current scope and future debt

- The legacy profile columns on `users` remain during the compatibility period and are still available to the public Blade flow. Their removal requires a separate migration after all consumers are migrated and a backup is verified.
- Administration currently uses the single `is_admin` flag plus verified email. A roles/permissions system is future work and is intentionally outside this feature.
- Photos, videos, plans, favorites, comments, historical audit logs and bulk actions are not implemented.
