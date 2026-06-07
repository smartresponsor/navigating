# Navigating W25 — RC condition deep check

W25 is a current-slice cleanup pass after W24 token normalization.

## RC blockers fixed

- Removed stale project-owned classes with the redundant secondary navigation token.
- Removed stale route bridge that still used the redundant secondary navigation token.
- Removed stale route-map trees that still used the redundant secondary navigation path segment.
- Removed old runtime-provider PHPUnit test that referenced removed runtime classes.
- Removed temporary PHPUnit file and PHPUnit result cache.

## Canonical state

The component token is `navigation`. The local CRUD grammar is:

- `navigation.index`
- `navigation.show_id`
- `navigation.show_slug`
- `navigation.new`
- `navigation.create`
- `navigation.edit_id`
- `navigation.edit_slug`
- `navigation.update_id`
- `navigation.update_slug`
- `navigation.delete_id`
- `navigation.delete_slug`
- `navigation.bulk`
- `navigation.import`
- `navigation.export`
- `navigation.archive_id`
- `navigation.archive_slug`
- `navigation.restore_id`
- `navigation.restore_slug`
- `navigation.duplicate_id`
- `navigation.duplicate_slug`

The physical admin prefix remains env-backed through `APP_BACK_TOKEN` with default `ea`.

## Non-goals

- No W16 rename was applied.
- No migrations were added.
- Native EasyAdmin API names such as `MenuItem` and `configureMenuItems()` remain because they are vendor API symbols, not Navigating-owned naming.
