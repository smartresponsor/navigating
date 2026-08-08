# W31 — DB-backed navigation administration on SQLite

## Purpose

Navigating moves menu ownership from hard-coded runtime YAML toward Doctrine-managed navigation records while preserving the existing runtime view-model and Interfacing projection contracts.

## Database model

SQLite remains the standalone development database.

The entity-first schema now contains:

- `navigation_menu` — menu/group identity, shell location, type, priority, enabled state and metadata;
- `navigation_item` — menu-owned tree nodes with optional parent relation, route/path target, visibility rules, ordering and metadata.

Doctrine associations replace string parent references:

- `NavigationItem.menu` is required;
- `NavigationItem.parent` is optional;
- a parent must belong to the same menu;
- deleting a menu cascades to its items.

No migrations are introduced. Local development schema remains metadata-driven.

## Objecting

Navigation entities use the Objecting audit embeddable pack instead of owning duplicate created/modified timestamp fields.

`objecting/object` is an explicit dependency.

## EasyAdmin

The native EasyAdmin backend exposes separate CRUD surfaces for menus and items.

Menu administration owns shell placement and group metadata. Item administration uses association selectors for menu and parent relations and typed Symfony form boundaries for JSON objects and JSON token lists.

## Cruding and Interfacing

`cruding/crud` and `interfacing/interface` are explicit component dependencies.

Navigating does not add generic business CRUD routes or controllers. Cruding remains the generic CRUD owner. Interfacing continues consuming Navigating's existing location projection/view-model boundary and does not depend on the persistence source.

## Runtime projection

`NavigationDatabaseConfigProvideService` projects enabled Doctrine records into the canonical normalized runtime configuration shape.

`NavigationShellProvideService` prefers the database projection when at least one enabled database menu exists. Existing YAML configuration remains a temporary bootstrap fallback while current menu inventory is migrated into database fixtures/data. Once the database inventory is complete, that fallback can be removed in a follow-up cleanup wave.

Parent relationships are projected as `metadata.parent_key` without changing the current shell item view-model contract.

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
composer qa
```

The SQLite schema should be regenerated from entity metadata rather than from a migration file.
