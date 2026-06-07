# Navigating W19 — left navigation CRUD index roots

W19 narrows the business-visible left navigation contract.

## Contract

`shell.left.middle` is only for non-system business roots.

Rules:

- every item is a `link`;
- every item points to a CRUD `index` route;
- every item represents a root domain entry such as `Vendor`, `Catalog`, `Order`, `Billing`;
- no headings;
- no show/edit/new/delete/import/export child operations;
- no system/admin roots.

`left_middle_business` item metadata is marked with:

- `navigation_scope: business`;
- `operation: index`;
- `root_only: true`;
- `crud_index_only: true`.

`side.left.bottom` is only for system/admin roots.

Rules:

- every item is a `link`;
- every item points to a CRUD `index` route;
- every item is visible only for `ROLE_ADMIN` or `ROLE_SUPER_ADMIN`;
- no business roots;
- no show/edit/new/delete/import/export child operations.

`left_bottom_platform` item metadata is marked with:

- `navigation_scope: system`;
- `operation: index`;
- `root_only: true`;
- `crud_index_only: true`;
- `admin_only: true`.

## Kept outside this patch

W19 does not change EasyAdmin route names, the default `/ea` token, Doctrine, migrations, or route HTTP methods.
