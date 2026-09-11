# W31 — DB-backed navigation administration on SQLite

## Purpose

Navigating moves menu ownership from hard-coded runtime YAML to Doctrine-managed navigation records while preserving the existing runtime view-model and Interfacing projection contracts.

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

`cruding/crud`, `viewing/view`, `interfacing/interface`, and `objecting/object` are explicit component dependencies.

Navigating does not add generic business CRUD routes or controllers. Cruding remains the generic CRUD owner. Interfacing continues consuming Navigating's existing location projection/view-model boundary and does not depend on the persistence source.

## Runtime projection

`NavigationDatabaseConfigProvideService` projects enabled Doctrine records into the canonical normalized runtime configuration shape.

Doctrine is the only runtime source for menu inventory. `NavigationShellProvideService` always replaces configuration `shell_groups` with the Doctrine projection. YAML `shell_groups` remain available only to the explicit bootstrap import command and are never used as a runtime fallback.

Structural navigation configuration remains configuration. This includes shell-location definitions, runtime role/scope/environment defaults and other non-menu component settings.

If Doctrine contains no enabled menu, runtime navigation fails explicitly with an instruction to bootstrap the database or create a menu. It never silently falls back to historical YAML menu content.

Parent relationships are projected as `metadata.parent_key` without changing the current shell item view-model contract.

## Bootstrap import and fixtures

The bootstrap command is:

```text
php bin/console navigation:database:import-config
```

It imports the currently merged configuration `shell_groups` inventory into `navigation_menu` and `navigation_item`, including menu visibility, item visibility, target metadata and parent relationships.

The command is intentionally non-destructive by default. If navigation rows already exist, it refuses to overwrite them. An explicit reset/import requires:

```text
php bin/console navigation:database:import-config --force
```

`NavigationFixture` uses the same transactional import implementation. It belongs to the `navigating` fixture group and must be loaded with `--append` so host application data is never purged:

```text
composer navigation:fixtures
```

The fixture prefers the promoted repository install manifest when one exists and otherwise restores the canonical merged configuration.

## Install manifest and backups

Navigating owns application-level recovery independently of hosting-provider database tools.

Promote the current administrator-managed state to the repository install state with:

```text
php bin/console navigation:manifest:write
```

The versioned manifest is written to `resources/navigation/navigation.install.json`. It can be verified with `navigation:manifest:verify` and restored with `navigation:manifest:restore --force`.

Runtime backups are separate from the install manifest:

```text
php bin/console navigation:backup:create
php bin/console navigation:backup:restore <path> --force
```

Snapshots are portable JSON, include the full functional menu/item configuration state, carry a format version and SHA-256 integrity value, and are restored transactionally through the same entity-first model.

Generated row IDs and historical Objecting audit timestamps are intentionally outside the portable recovery contract.

Automatic rolling backups are written only when a navigation flush occurs outside an explicit Doctrine transaction, preventing uncommitted import/restore state from becoming the latest automatic recovery snapshot.

See `docs/navigation-recovery.md` for complete recovery procedures.

## Host-safe schema lifecycle

Navigating never uses global `doctrine:schema:update --force` as its component maintenance command inside the host application.

For normal entity evolution use:

```text
php bin/console navigation:database:update
```

or:

```text
composer navigation:schema:update
```

The command temporarily whitelists only `navigation_menu` and `navigation_item` through Doctrine's schema-assets filter, passes only `NavigationMenu` and `NavigationItem` metadata to ORM `SchemaTool`, performs the schema synchronization, then restores the host application's previous schema-assets filter in `finally`.

The command uses the Doctrine ORM 3.x `SchemaTool::getUpdateSchemaSql(array $classes)` / `updateSchema(array $classes)` API. It does not rely on the removed legacy `saveMode` argument.

This is component-scoped rather than globally additive-only. Doctrine can emit DDL needed to synchronize Navigating's own tables, so planned updates should use an explicit recovery point:

```text
composer navigation:schema:safe
```

This runs `navigation:backup:create` followed by `navigation:database:update`.

If Navigating entity evolution requires a deliberate destructive reset inside the component, use:

```text
php bin/console navigation:database:rebuild --force
```

The rebuild snapshots the current navigation state, drops/recreates only Navigating metadata tables, and restores the snapshot. It does not own unrelated host schema.

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

For an existing W31 database with administrator-managed navigation, create a backup before component schema synchronization or destructive rebuild. Use `--force` on import/restore only when an intentional replacement is desired.

The SQLite schema remains generated from entity metadata rather than from a migration file.
