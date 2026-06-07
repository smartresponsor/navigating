# Navigating W17: CRUD route HTTP methods

W17 is based on W15. W16 is intentionally skipped.

## Scope

This patch only tightens HTTP method grammar for canonical `navigation.*` route bridge entry points.

It does not rename EasyAdmin route names and does not change the default back-office prefix.

## Method grammar

Read routes:

- `navigation.index` -> `GET`
- `navigation.show_id` -> `GET`
- `navigation.show_slug` -> `GET`
- `navigation.new` -> `GET`
- `navigation.edit_id` -> `GET`
- `navigation.edit_slug` -> `GET`
- `navigation.export` -> `GET`

Write/mutation routes:

- `navigation.create` -> `POST`
- `navigation.update_id` -> `POST`
- `navigation.update_slug` -> `POST`
- `navigation.delete_id` -> `POST`
- `navigation.delete_slug` -> `POST`
- `navigation.bulk` -> `POST`
- `navigation.archive_id` -> `POST`
- `navigation.archive_slug` -> `POST`
- `navigation.restore_id` -> `POST`
- `navigation.restore_slug` -> `POST`
- `navigation.duplicate_id` -> `POST`
- `navigation.duplicate_slug` -> `POST`

Mixed form workflow:

- `navigation.import` -> `GET|POST`

## Boundary

Native EasyAdmin generated routes can still be shown as `ANY` by the EasyAdmin route loader. The stable public/admin grammar owned by Navigating is `navigation.*`.
