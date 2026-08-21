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

No Doctrine migrations are introduced for the current development workflow. Navigating schema ownership is component-scoped:

```text
php bin/console navigation:database:update
```

The command temporarily whitelists only `navigation_menu` and `navigation_item` in Doctrine schema introspection, synchronizes only `NavigationMenu` and `NavigationItem` metadata through the Doctrine ORM 3.x `SchemaTool` API, and restores the host application's previous schema-assets filter afterward. Navigating does not use global `doctrine:schema:update --force` as its host-application maintenance command.

Because schema synchronization can still alter Navigating's own tables, planned updates should use:

```text
composer navigation:schema:safe
```

which creates a portable Navigating backup before the component-scoped schema update.

Deliberate destructive Navigating schema evolution uses:

```text
php bin/console navigation:database:rebuild --force
```

which snapshots the navigation configuration, recreates only Navigating tables, and restores the snapshot.

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

Create/update the Navigating schema with:

```text
php bin/console navigation:database:update
```

Current configuration inventory is imported once with:

```text
php bin/console navigation:database:import-config
```

The command refuses to overwrite an existing navigation database by default. An intentional reset requires:

```text
php bin/console navigation:database:import-config --force
```

If runtime contains no enabled Doctrine menus, Navigating fails explicitly with bootstrap guidance instead of silently falling back to YAML menu content.

## Fixtures and recovery

Canonical fixtures are isolated in the `navigating` fixture group. Use only the host-safe command:

```text
composer navigation:fixtures
```

which loads with `--group=navigating --append` and therefore does not purge unrelated host data.

Navigating also supports:

```text
navigation:backup:create
navigation:backup:restore <path> --force
navigation:manifest:write
navigation:manifest:verify
navigation:manifest:restore --force
```

and keeps rolling `auto-latest.json` / `auto-previous.json` recovery snapshots under `var/backup/navigating/` after successful navigation writes outside explicit Doctrine transactions. Transactional import/restore state is never promoted as an automatic backup before commit.

See `docs/navigation-recovery.md` for the full recovery canon.

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
php bin/console navigation:database:update
php bin/console doctrine:schema:validate
php bin/console navigation:database:import-config
php bin/console navigation:manifest:write
php bin/console navigation:manifest:verify
composer navigation:schema:safe
php bin/console navigation:database:rebuild --force
composer navigation:fixtures
composer qa
```

The SQLite schema is generated from Navigating entity metadata rather than a migration file.
