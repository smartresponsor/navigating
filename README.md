# Navigating W31 — DB-backed navigation administration

Navigating owns navigation intent and its persistence model. It does not execute the business actions, widgets, filters, voters, or CRUD operations referenced by navigation targets.

## Canon

Menu inventory is Doctrine-backed. Runtime menu content is read from `NavigationMenu` and `NavigationItem`; YAML `shell_groups` exist only as bootstrap import input and are never a runtime fallback.

Structural navigation configuration remains configuration, including shell locations and runtime role/scope/environment defaults.

## Visibility dimensions

Both menus and items support:

- `visible_for_roles`
- `visible_for_scopes`
- `visible_for_environments`

All dimensions are optional. When a dimension is empty, the node is visible for that dimension.

Runtime context may come from request attributes:

- `_navigation_scopes` or `navigation_scopes`
- `_navigation_environment` or `navigation_environment`

Fallback runtime context remains configuration:

```yaml
navigation:
  runtime_scopes:
    fallback_scopes: [user, system]
  runtime_environment:
    fallback_environment: dev
```

## HTTP and runtime boundary

Standalone HTTP entry points are served by `Service/Http/Navigation/NavigationHttpService`.

The canonical runtime engine lives in:

```text
src/Service/Navigation/{Normalize,Validate,Filter,Build,Resolve,Provide,Render,Merge,Cache}
src/ServiceInterface/Navigation/{Provide,Resolve,Render,...}
src/Model/Navigation/View/
```

Twig integration remains under `Service/Twig/Navigation` and exposes:

```twig
{% set shell = navigating_shell() %}
{% set group = navigating_group('shell.left.middle') %}
{{ navigating_render('shell.left.middle') }}
```

Twig and Interfacing do not discover persistence, routes, or configuration files. They consume the Navigating view-model/projection boundary.

## Doctrine SQLite entity-first model

Standalone development uses SQLite. The entity-first schema contains:

```text
navigation_menu
navigation_item
```

`NavigationMenu` owns menu identity, location, type, visibility, priority, enabled state and metadata.

`NavigationItem` belongs to one menu and may reference another item in the same menu as its parent. String `parentKey` persistence is obsolete; hierarchy is a Doctrine association.

Navigation item keys are unique within a menu rather than globally because keys such as `vendor`, `catalog`, or `attachment` can legitimately occur in multiple menus.

No Doctrine migrations are introduced for the current development workflow. Schema creation remains metadata-driven through `doctrine:schema:update --force` in standalone/dev mode.

## Objecting

`NavigationMenu` and `NavigationItem` use the Objecting audit embeddable lifecycle pack for created/modified state. Duplicate local timestamp implementations are not maintained.

## EasyAdmin

Navigating exposes native EasyAdmin administration under the environment-backed backend prefix (`APP_BACK_TOKEN`, default `ea`). The admin surface is protected by `ROLE_ADMIN`.

Admin entry points live in `src/Controllers/Admin/` as the platform's explicit EasyAdmin exception to the zero generic CRUD controller rule.

EasyAdmin provides separate CRUD surfaces for:

- navigation menus;
- navigation items.

Menu administration controls shell placement, group visibility and metadata. Item administration uses Doctrine association selectors for menu and parent plus typed Symfony Form Types for JSON objects and token lists.

Native EasyAdmin templates are used; no component-owned backend Twig CRUD templates are introduced.

## Cruding

Navigating does not implement generic business CRUD routes or controllers. `cruding/crud` is an explicit dependency and remains the owner of generic application CRUD grammar and execution.

Navigation entities are therefore available to the host application's Cruding integration without duplicating CRUD mechanics inside Navigating.

## Interfacing

`interfacing/interface` is an explicit dependency. Interfacing consumes Navigating's existing location/view-model projection and does not depend on whether navigation content came from SQLite, cache, EasyAdmin, or another Doctrine-backed write path.

## Bootstrap

Current configuration inventory is imported once with:

```text
php bin/console navigation:database:import-config
```

The command refuses to overwrite an existing navigation database by default. An intentional reset requires:

```text
php bin/console navigation:database:import-config --force
```

If runtime contains no enabled Doctrine menus, Navigating fails explicitly with bootstrap guidance instead of silently falling back to YAML menu content.

## Cache

The Doctrine runtime projection is cached through Symfony `cache.app`.

`NavigationConfigCacheInvalidationSubscriber` invalidates the projection after Doctrine persist, update, or remove events for `NavigationMenu` and `NavigationItem`. This covers writes made through EasyAdmin, Cruding, bootstrap import, or another Doctrine-backed operation.

## Runtime ownership and activation

Navigation publication retains namespace-owned runtime activation. Component ownership is derived from item namespace metadata rather than URI prefixes or labels. Role, request-scope, environment and runtime activation remain separate filtering dimensions.

## Platform baseline

- PHP 8.4
- Symfony 8.1
- Doctrine ORM 3.3+
- DoctrineBundle 3.2+
- EasyAdmin 5
- Objecting
- Cruding
- Interfacing

## Local validation

Run after dependency/schema changes:

```text
composer update
composer validate
php bin/console lint:container
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --force
php bin/console navigation:database:import-config
composer qa
```

The SQLite schema is regenerated from entity metadata rather than a migration file.
