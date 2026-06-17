# Navigating EasyAdmin Controller Rules

Marker: `EASYADMIN_NATIVE_EXCEPTION`

This directory is an explicit exception to the platform-wide zero generic CRUD controller rule.

## Ownership

Navigating owns its native EasyAdmin administration surface for `NavigationItem`:

- `DashboardController.php` owns the EasyAdmin dashboard entry point;
- `NavigationItemCrudController.php` owns native EasyAdmin CRUD page and action composition;
- `config/routes/easyadmin.yaml` imports `easyadmin.routes` with the environment-backed back-office prefix;
- `src/Form/Type/Admin/*Type.php` owns the Symfony Form Type grammar used by EasyAdmin fields.

These files are not legacy generic CRUD delivery. They are framework integration points required by EasyAdmin and are protected by `ROLE_ADMIN`.

## Required invariants

- Keep namespace `App\Navigating\Controllers\Admin` for compatibility with the host and compiled Symfony containers.
- Keep `DashboardController` based on `AbstractDashboardController` and `#[AdminDashboard]`.
- Keep `NavigationItemCrudController` based on `AbstractCrudController` and bound to `NavigationItem::class`.
- Keep the back-office prefix environment-backed; do not hardcode `/ea` in PHP.
- Keep native EasyAdmin templates and Symfony Form Type boundaries.
- Do not replace these controllers with Cruding generic routes or generic CRUD controllers.

## Files that must remain absent

The previous generic delivery layer is obsolete and must not be restored:

- `NavigationCrudRouteController.php`;
- `config/routes/navigation_admin_crud.yaml`.

## Change protocol

Do not remove or rename the native EasyAdmin controllers as part of broad controller or CRUD-route cleanup.
Any ownership migration must be atomic and must update the host integration, Composer dependency, route loader, security rule, tests, README, and this file in the same reviewed change.

Before completing a change in this directory, run:

```bash
composer qa:navigating-easyadmin
```
