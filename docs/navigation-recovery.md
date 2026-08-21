# Navigating database recovery

Navigating treats Doctrine as the runtime source of truth, but menu configuration must remain recoverable without hosting-provider database tooling.

## Recovery layers

Navigating maintains three independent recovery paths.

### 1. Canonical fixtures

`App\Navigating\DataFixtures\NavigationFixture` restores the canonical install state.

The fixture belongs only to the Doctrine fixture group `navigating`. The canonical command is deliberately host-safe:

```text
php bin/console doctrine:fixtures:load --group=navigating --append --no-interaction
```

or:

```text
composer navigation:fixtures
```

`--append` is mandatory for the product command because DoctrineFixturesBundle's default purge behavior can delete unrelated host-application data. The fixture itself transactionally replaces only Navigating menu rows through `NavigationConfigImportService`.

The fixture prefers `resources/navigation/navigation.install.json` when that manifest exists. Until the first promoted manifest is written, it falls back to the repository merged navigation configuration. Both paths use the same transactional import service as the CLI bootstrap command.

### 2. Install manifest

The install manifest is a versioned portable JSON snapshot stored at:

```text
resources/navigation/navigation.install.json
```

Promote the current database state to the repository install state with:

```text
php bin/console navigation:manifest:write
```

Verify repository manifest drift with:

```text
php bin/console navigation:manifest:verify
```

Restore it without DoctrineFixturesBundle with:

```text
php bin/console navigation:manifest:restore --force
```

After intentional administrative changes that should become defaults for new installations, run `navigation:manifest:write`, verify it, and commit the generated manifest.

### 3. Runtime backups

A runtime backup captures the current mutable administrative state independently of the install manifest:

```text
php bin/console navigation:backup:create
```

Default location:

```text
var/backup/navigating/navigation-YYYYmmdd-HHMMSS.json
```

An explicit path may be supplied when the backup should live in a persistent/shared deployment directory:

```text
php bin/console navigation:backup:create shared/backup/navigation-before-update.json
```

Restore with:

```text
php bin/console navigation:backup:restore shared/backup/navigation-before-update.json --force
```

Snapshots contain the complete functional navigation configuration: all menus and items, disabled state, visibility, custom slugs, targets, metadata, parent relationships and archived state. Each snapshot has a format version and SHA-256 integrity value. Restore verifies the checksum before changing Doctrine state.

Portable snapshots intentionally do not promise preservation of generated database row IDs or historical Objecting audit timestamps. Those are persistence history, not navigation configuration. Relationships are restored from stable menu/item business keys so the resulting menu behavior is equivalent after a clean install or rebuild.

## Automatic rolling backups

Navigating also maintains an application-owned rolling recovery pair whenever a Doctrine flush changes a `NavigationMenu` or `NavigationItem`:

```text
var/backup/navigating/auto-latest.json
var/backup/navigating/auto-previous.json
```

`auto-latest.json` is written atomically through a temporary file. Before promotion, the prior latest snapshot is copied to `auto-previous.json`. An empty navigation state is never promoted over the last non-empty automatic backup.

Automatic backup runs only after a navigation flush that is not inside an explicit Doctrine transaction. This prevents a snapshot from being promoted from uncommitted import/restore state. Transactional import/restore paths already have their explicit recovery sources.

Automatic backup is a best-effort safety layer. A filesystem failure is logged and does not roll back the business write. For planned schema work, the explicit manual/pre-operation backup remains authoritative.

## Host-safe schema update

Navigating must not use the host application's global Doctrine schema update as its component maintenance mechanism.

For normal entity evolution use:

```text
php bin/console navigation:database:update
```

or:

```text
composer navigation:schema:update
```

The command temporarily restricts Doctrine's schema-assets filter to `navigation_menu` and `navigation_item`, passes only `NavigationMenu` and `NavigationItem` metadata to ORM `SchemaTool`, synchronizes those tables, and restores the host application's original schema-assets filter in `finally`.

This is component-scoped, not globally additive-only: Doctrine may still emit DDL needed to synchronize Navigating's own tables. It cannot treat unrelated host tables as schema targets because they are hidden from the comparison. For that reason planned updates should still use an explicit navigation backup.

For a planned update with an explicit recovery point use:

```text
composer navigation:schema:safe
```

which performs:

```text
navigation:backup:create
navigation:database:update
```

Do not use `doctrine:schema:update --force` as the Navigating component update command in the host application.

If an entity change requires a deliberate destructive reset inside Navigating itself, use the component rebuild path below so the menu configuration is snapshotted before tables are recreated.

## Component-scoped full rebuild

When Navigating's own tables must be destroyed and recreated, use:

```text
php bin/console navigation:database:rebuild --force
```

or:

```text
composer navigation:rebuild
```

The command performs the following sequence inside the application:

```text
create portable Navigating snapshot
write snapshot under var/backup/navigating/
drop only NavigationMenu and NavigationItem Doctrine metadata tables
recreate only those Navigating tables
restore the snapshot
```

It uses Doctrine `SchemaTool` with the metadata for `NavigationMenu` and `NavigationItem`. It does not execute `doctrine:schema:drop`, does not use `--full-database`, and therefore does not own or destroy unrelated host application tables.

An explicit pre-rebuild backup path may be supplied:

```text
php bin/console navigation:database:rebuild --force --backup-path=shared/backup/navigation-pre-rebuild.json
```

## Complete database loss

For a completely removed SQLite database, create the Navigating schema with:

```text
navigation:database:update
```

Then restore a runtime backup when preserving the latest administrative state, or use the install manifest for a canonical installation:

```text
navigation:manifest:restore --force
```

or via the safe Navigating fixture group:

```text
composer navigation:fixtures
```

The host database backup facility is therefore optional for Navigating configuration recovery. Application-level snapshots remain portable and database-engine independent.

## Operational canon

Before any intentional schema operation that may rebuild or alter Navigating tables, create an application-level Navigating backup first. For normal entity-driven updates use `composer navigation:schema:safe`. For a deliberate Navigating-only table reset use `navigation:database:rebuild --force` instead of dropping or globally updating the whole host database.

Never use an unscoped `doctrine:fixtures:load` as a Navigating recovery command inside the host application. Always use the `navigating` group with `--append`, or use manifest/backup restore commands.

Never use global `doctrine:schema:update --force` as Navigating's own maintenance command inside the host application. Navigating schema ownership is limited to its own Doctrine metadata and schema-assets whitelist.

When administrative changes become part of the product's canonical default state, promote them with `navigation:manifest:write`, validate with `navigation:manifest:verify`, and commit the resulting manifest. Runtime backups remain operational artifacts under `var/` or another deployment-owned persistent path and are not repository fixtures.
