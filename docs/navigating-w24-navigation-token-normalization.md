# Navigating W24 — navigation token normalization

W24 removes the redundant project-owned menu token because the Navigating component already owns the navigation concern.

Canonical result:

- route names use `navigation.*`;
- paths use `/navigation/*`;
- persistence uses `NavigationItem`;
- admin CRUD uses `NavigationItemCrudController`;
- canonical CRUD bridge uses `NavigationCrudRouteController`;
- repository uses `NavigationItemRepository`;
- Symfony form types use `NavigationItemLocationType` and `NavigationItemOperationType`;
- SQLite table is `navigation_item`;
- stable item key column is `navigation_key`;
- metadata scope key is `navigation_scope`;
- route-map file is `config/platform/routes/crud/navigation.yaml`.

W24 does not include W16 and does not add migrations. Entity-first schema update remains the local SQLite design path.

The only remaining `MenuItem` token is the external EasyAdmin API class/method used by `DashboardController`. It is not a project-owned Navigating token.
