# Navigating W12 — EasyAdmin Symfony Type layer

W12 keeps the W11 entity-first database design and adds a Symfony Form Type boundary for EasyAdmin CRUD fields.

## Decision

EasyAdmin CRUD controllers must not own low-level field option grammar such as enum choices or JSON textarea transformation.

The controller should only attach Symfony form types to EasyAdmin fields:

- `NavigationItemLocationType`
- `NavigationItemOperationType`
- `JsonArrayTextareaType`

## Scope

- No migrations.
- No `/src/Domain`.
- No Port/Adapter layer.
- Native EasyAdmin CRUD remains the admin entry point.
- Doctrine ORM + SQLite remains entity-first.

## Runtime shape

`NavigationItemCrudController` owns EasyAdmin page/action composition.

`src/Form/Type/Admin/*Type.php` owns Symfony form input grammar:

- shell location choices;
- CRUD operation choices;
- JSON object editing for `routeParameters` and `metadata`.

## JSON fields

`JsonArrayTextareaType` transforms array data into a pretty JSON object and rejects invalid JSON or list-shaped JSON.

Accepted:

```json
{
  "id": 1
}
```

Rejected:

```json
[
  "id"
]
```

The database contract stays entity-first through Doctrine attributes on `NavigationItem`.
