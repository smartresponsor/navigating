# Navigating W11 — Entity-first DB design, no migrations

W11 keeps the EasyAdmin + Doctrine SQLite decision from W10, but makes the database contract explicit in the Doctrine entity first.

## Decision

- Database design is owned by `App\Navigating\Entity\NavigationItem`.
- No Doctrine migration files are added.
- Standalone/dev schema is produced from entity metadata with Doctrine schema tooling.
- SQLite remains the default standalone database.
- EasyAdmin CRUD works over the entity contract, not over YAML-only navigation definitions.

## Table

```text
navigation_item
```

## Entity-first columns

```text
id               integer primary key
navigation_key         varchar(160), unique, stable business key
parent_key       varchar(160), nullable, tree parent business key
label            varchar(140)
route_name       varchar(180)
route_parameters json
location         varchar(120)
operation        varchar(60)
icon             varchar(80), nullable
required_role    varchar(80), nullable
position         integer
enabled          boolean
metadata         json
created_at       datetime immutable
updated_at       datetime immutable
```

## Indexes

```text
uniq_navigation_item_navigation_key
idx_navigation_item_route_name
idx_navigation_item_operation
idx_navigation_item_parent_key
idx_navigation_item_enabled_location_position
```

## Local schema commands

```powershell
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
```

## Explicitly not added

```text
migrations/
src/Migrations/
doctrine/doctrine-migrations-bundle
```

This is intentional for the current component phase: the DB design is entity-first and the schema is generated directly from metadata.
