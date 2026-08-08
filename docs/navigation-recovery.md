# Navigating database recovery

Navigating treats Doctrine as the runtime source of truth, but menu configuration must remain recoverable without hosting-provider database tooling.

## Recovery layers

Navigating maintains three independent recovery paths.

### 1. Canonical fixtures

`App\Navigating\DataFixtures\NavigationFixture` restores the canonical install state.

The fixture prefers `resources/navigation/navigation.install.json` when that manifest exists. Until the first promoted manifest is written, it falls back to the repository merged navigation configuration. Both paths use the same transactional import service as the CLI bootstrap command.

Use:

```text
php bin/console doctrine:fixtures:load --no-interaction
```

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

Snapshots contain all menus and items, including disabled state, visibility, custom slugs, targets, metadata, parent relationships and archived state. Each snapshot has a format version and SHA-256 integrity value. Restore verifies the checksum before changing Doctrine state.

## Safe schema update

Do not run a destructive schema update as the first operation when Navigating contains administrator-managed configuration.

The repository provides:

```text
composer navigation:schema:safe
```

This performs:

```text
navigation:backup:create
doctrine:schema:update --force
```

The backup is created before Doctrine touches the schema.

## Complete database loss

For a completely removed SQLite database, the recovery order is:

```text
create database/schema
restore runtime backup, when preserving the latest administrative state
```

or, for a clean canonical installation:

```text
create database/schema
navigation:manifest:restore
```

or:

```text
doctrine:fixtures:load
```

The host database backup facility is therefore optional for Navigating configuration recovery. Application-level snapshots remain portable and database-engine independent.
