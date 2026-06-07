# W23 — group metadata config hotfix

W23 fixes the Symfony configuration tree error caused by group-level `metadata` in `config/navigation.yaml`.

The `navigation.shell_groups.*` schema allows structural group fields only:

- `label`
- `location`
- `type`
- `priority`
- `enabled`
- `visible`
- visibility boundaries
- `items`

Business/navigation semantics stay on item-level `metadata`, where they are already supported and consumed by the runtime engine.

This keeps the shell group as a layout/slot container and keeps navigation intent metadata on concrete renderable items.

No migrations are added.
W16 skipped changes are not included.
