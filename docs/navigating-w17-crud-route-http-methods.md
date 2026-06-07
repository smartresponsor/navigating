# Navigating W17: CRUD route HTTP methods

W17 is based on W15. W16 is intentionally skipped.

## Scope

This patch only tightens HTTP method grammar for canonical `navigation.menu.*` route bridge entry points.

It does not rename EasyAdmin route names and does not change the default back-office prefix.

## Method grammar

Read routes:

- `navigation.menu.index` -> `GET`
- `navigation.menu.show_id` -> `GET`
- `navigation.menu.show_slug` -> `GET`
- `navigation.menu.new` -> `GET`
- `navigation.menu.edit_id` -> `GET`
- `navigation.menu.edit_slug` -> `GET`
- `navigation.menu.export` -> `GET`

Write/mutation routes:

- `navigation.menu.create` -> `POST`
- `navigation.menu.update_id` -> `POST`
- `navigation.menu.update_slug` -> `POST`
- `navigation.menu.delete_id` -> `POST`
- `navigation.menu.delete_slug` -> `POST`
- `navigation.menu.bulk` -> `POST`
- `navigation.menu.archive_id` -> `POST`
- `navigation.menu.archive_slug` -> `POST`
- `navigation.menu.restore_id` -> `POST`
- `navigation.menu.restore_slug` -> `POST`
- `navigation.menu.duplicate_id` -> `POST`
- `navigation.menu.duplicate_slug` -> `POST`

Mixed form workflow:

- `navigation.menu.import` -> `GET|POST`

## Boundary

Native EasyAdmin generated routes can still be shown as `ANY` by the EasyAdmin route loader. The stable public/admin grammar owned by Navigating is `navigation.menu.*`.
