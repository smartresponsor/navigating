# Navigating W13 — CRUD route grammar entry points

W13 aligns physical admin entry points with the Cruding route grammar. Routes are imported under `APP_BACK_TOKEN`, guarded by `ROLE_ADMIN`, and resolved into native EasyAdmin CRUD actions. No migrations are introduced; database design remains entity-first.
