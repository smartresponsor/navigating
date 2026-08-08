# W31 — DB-backed navigation administration on SQLite

## Purpose

Navigating moves menu ownership from hard-coded runtime YAML toward Doctrine-managed navigation records while preserving the existing runtime view-model and Interfacing projection contracts.

## Database model

SQLite remains the standalone development database.

The entity-first schema now contains:

- `navigation_menu` — menu/group identity, shell location, type, visibility, priority, enabled state and metadata;
- `navigation_item` — menu-owned tree nodes with optional parent relation, route/path target, visibility rules, ordering and metadata.

Doctrine associations replace string parent references:

- `NavigationItem.menu` is required;
- `NavigationItem.parent` is optional;
- a parent must belong to the same menu;
- deleting a menu cascades to its items.

Navigation item business keys are unique inside a menu (`menu_id + navigation_key`). This is required because the same logical item key can legitimately appear in multiple menus, for example `vendor` in both the left navigation and quick menu.

No migrations are introduced. Local development schema remains metadata-driven.

## Objecting

Navigation entities use the Objecting audit embeddable pack instead of owning duplicate created/modified timestamp fields. Both menu and item entities update the Objecting modified lifecycle value through Doctrine `PreUpdate` callbacks.

`objecting/object` is an explicit dependency.

## EasyAdmin

The native EasyAdmin backend exposes separate CRUD surfaces for menus and items.

Menu administration owns shell placement, group visibility and metadata. Item administration uses association selectors for menu and parent relations and typed Symfony form boundaries for JSON objects and JSON token lists.

## Cruding and Interfacing

`cruding/crud` and `interfacing/interface` are explicit component dependencies.

Navigating does not add generic business CRUD routes or controllers. Cruding remains the generic CRUD owner. Interfacing continues consuming Navigating's existing location projection/view-model boundary and does not depend on the persistence source.

## Runtime projection

`NavigationDatabaseConfigProvideService` projects enabled Doctrine records into the canonical normalized runtime configuration shape.

`NavigationShellProvideService` prefers the database projection when at least one enabled database menu exists. Existing YAML menu inventory remains a temporary bootstrap fallback until the current inventory has been imported into SQLite. Structural navigation configuration such as shell locations and runtime defaults remains configuration and is not menu content.

Parent relationships are projected as `metadata.parent_key` without changing the current shell item view-model contract.

## Bootstrap import

The one-time bootstrap command is:

```text
php bin/console navigation:database:import-config
```

It imports the currently merged `shell_groups` inventory into `navigation_menu` and `navigation_item`, including menu visibility, item visibility, target metadata and parent relationships.

The command is intentionally non-destructive by default. If navigation rows already exist, it refuses to overwrite them. An explicit reset/import requires:

```text
php bin/console navigation:database:import-config --force
```

After the initial database import has been validated in the host application, the temporary runtime YAML fallback can be removed in the cleanup wave. Administrative changes after that point are made through EasyAdmin/Cruding and persisted in Doctrine.

## Cache

The Doctrine-backed runtime projection is cached through Symfony `cache.app`.

`NavigationConfigCacheInvalidationSubscriber` listens to Doctrine persist, update and remove events for both navigation entities. Changes made through EasyAdmin, Cruding or another Doctrine-backed application operation therefore invalidate the same cache automatically.

The cache stores only the normalized DB projection; Interfacing remains unaware of persistence and caching details.

## Platform baseline

W31 aligns Navigating with the current component baseline:

- PHP 8.4;
- Symfony 8.1;
- Doctrine ORM 3.3+;
- DoctrineBundle 3.2+;
- EasyAdmin 5;
- Objecting, Cruding and Interfacing as explicit dependencies.

## Required local validation

After dependency update in the workspace, run:

```text
composer update
composer validate
php bin/console lint:container
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --force
php bin/console navigation:database:import-config
composer qa
```

For an existing W31 test database that already contains navigation rows, use the import command without `--force` first. Use `--force` only when an intentional navigation reset is desired.

The SQLite schema should be regenerated from entity metadata rather than from a migration file.
