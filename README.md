# Navigating W7 — controller evacuation and QA cleanup

This slice keeps the shell v3 and typed item contracts and adds a lightweight visibility context layer.

## Canon

Navigating describes UI intent only. It does not execute actions, widgets, filters, voters, or CRUD operations.

## Visibility dimensions

- `visible_for_roles`
- `visible_for_scopes`
- `visible_for_environments`

All dimensions are optional. When a dimension is empty, the node is visible for that dimension.

## Runtime context

Runtime context may come from request attributes:

- `_navigation_scopes` or `navigation_scopes`
- `_navigation_environment` or `navigation_environment`

Fallback config:

```yaml
navigation:
  runtime_scopes:
    fallback_scopes: [user, system]
  runtime_environment:
    fallback_environment: dev
```

## W6 intent

This is intentionally not Symfony voter integration. It is a deterministic shell visibility filter for user-facing shell data.


## W7 cleanup

- Standalone HTTP entry points are served by `Service/Http/Navigation/NavigationHttpService`.
- `src/Controller` is intentionally absent.
- PHPStan configuration no longer references missing local stubs.
- Nested item keys are rejected independently: `items`, `sections`, or `children`.

## W9 EasyAdmin admin surface

Navigating exposes admin entry points under `/ea/*` through EasyAdmin 5.

Canon:

- `/ea/*` is admin-only and guarded by `ROLE_ADMIN`.
- EasyAdmin dependency is `easycorp/easyadmin-bundle:^5.0`.
- Admin entry points live in `src/Controllers/Admin/`.
- Native EasyAdmin templates are used; no component-owned backend Twig templates are introduced for admin CRUD actions.
- Navigating does not invent a Doctrine entity for YAML menu configuration.

Registered admin entry points:

- `ea_navigation_menu_index`
- `ea_navigation_menu_new`
- `ea_navigation_menu_create`
- `ea_navigation_menu_detail`
- `ea_navigation_menu_edit`
- `ea_navigation_menu_update`
- `ea_navigation_menu_delete`
- `ea_navigation_menu_import`
- `ea_navigation_menu_export`

The shell config points CRUD-like menu links to EasyAdmin routes instead of raw `/list`, `/create`, `/import`, `/export` paths.
