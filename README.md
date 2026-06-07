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
- Navigating does not invent a Doctrine entity for YAML navigation configuration.

Registered admin entry points:

- `ea_navigation_index`
- `ea_navigation_new`
- `ea_navigation_create`
- `ea_navigation_detail`
- `ea_navigation_edit`
- `ea_navigation_update`
- `ea_navigation_delete`
- `ea_navigation_import`
- `ea_navigation_export`

The shell config points CRUD-like navigation links to EasyAdmin routes instead of raw `/list`, `/create`, `/import`, `/export` paths.


## W10 EasyAdmin Doctrine SQLite

The admin surface is now native EasyAdmin CRUD backed by Doctrine ORM and SQLite. The back-office prefix is environment-backed through `APP_BACK_TOKEN` with default `ea`; PHP attributes no longer hardcode `/ea`.

## W11 — Entity-first DB design

- `NavigationItem` is now the source of truth for the SQLite schema.
- No Doctrine migrations are introduced.
- Schema creation remains metadata-driven through `doctrine:schema:update --force` in standalone/dev mode.
- The entity now defines stable business keys, parent keys, route metadata, role metadata, timestamps, uniqueness, and query indexes.

## W12 EasyAdmin Symfony Type layer

Navigating admin CRUD uses native EasyAdmin controllers with Symfony Form Type boundaries for field grammar.

- `src/Form/Type/Admin/NavigationItemLocationType.php` owns shell location choices.
- `src/Form/Type/Admin/NavigationItemOperationType.php` owns CRUD operation choices.
- `src/Form/Type/Admin/JsonArrayTextareaType.php` owns JSON object editing for route parameters and metadata.

The EA CRUD controller wires these types with `setFormType()` and does not inline choice arrays in `configureFields()`.

No Doctrine migrations are introduced; DB design remains entity-first.

## W18 business-visible navigation surface

W18 replaces the placeholder shell navigation with business-visible platform navigation for left navigation, context CRUD actions, toolbar, toggles, tools, filters, and footer. Platform route-map entries are used as canonical metadata; cross-platform links use path targets so the standalone shell stays renderable before every target route is registered natively.

## W19 left navigation CRUD index roots

W19 constrains `shell.left.middle` to non-system business CRUD index roots only and `shell.left.bottom` to admin/system CRUD index roots only. Both slots are root navigation groups, not operation groups.


## W20 — context related index roots

`shell.context.top` now contains only related business entity CRUD index roots. `shell.context.bottom` now contains only related system/admin entity CRUD index roots and is guarded by `ROLE_ADMIN`/`ROLE_SUPER_ADMIN`.

## W21 navigation render surface

W21 adds the canonical Twig/runtime bridge for navigation display. Twig should not discover routes or parse route maps. It should call Navigating and render the returned view model.

Canonical Twig functions:

```twig
{% set shell = navigating_shell() %}
{% set group = navigating_group('shell.left.middle') %}
{{ navigating_render('shell.left.middle') }}
```

Canonical implementation paths:

```text
src/Service/Twig/Navigation/
src/Service/Navigation/Provide/
src/Service/Navigation/Resolve/
src/Service/Navigation/Render/
src/ServiceInterface/Navigation/{Provide,Resolve,Render}/
src/Model/Navigation/View/
```

## W22 canonical runtime engine

W22 removes the temporary runtime wrapper layer. The canonical engine now lives in `Service/Navigation/{Normalize,Validate,Filter,Build,Resolve,Provide,Render,Merge}` with view models in `Model/Navigation/View`.

Twig integration remains under `Service/Twig/Navigation` and exposes:

```twig
{% set shell = navigating_shell() %}
{% set group = navigating_group('shell.left.middle') %}
{{ navigating_render('shell.left.middle') }}
```

There is no `src/Twig`, no `src/Dto`, no flat `NavigationRuntimeProvider`, and no migration layer.

## W23 — group metadata config hotfix

W23 removes unsupported group-level `metadata` from `config/navigation.yaml`. Shell groups remain structural containers; business/navigation semantics remain on item-level `metadata`, which is supported by the configuration tree and canonical runtime engine.


## W24 navigation token normalization

W24 removes the redundant project-owned navigation subtoken from Navigating CRUD names. Canonical routes are now `navigation.*`, paths are `/navigation/*`, and admin persistence uses `NavigationItem`/`navigation_item` with `navigation_key`. W16 remains skipped and migrations remain absent.
