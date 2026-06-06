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
