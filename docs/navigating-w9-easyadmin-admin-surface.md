# Navigating W9 EasyAdmin admin surface

W9 creates the physical admin entry-point surface for Navigating CRUD-like menu operations.

## Canon

- Admin URL prefix: `/ea/*`.
- Required role: `ROLE_ADMIN`.
- EasyAdmin dependency: `easycorp/easyadmin-bundle:^5.0`.
- Admin controllers path: `src/Controllers/Admin/`.
- Templates: native EasyAdmin templates only.
- No custom Twig backend templates were introduced.
- No Doctrine entity was invented inside Navigating.

## Entry points

The EasyAdmin dashboard is registered with:

- dashboard route path: `/ea`
- dashboard route name: `ea`

The navigation menu admin controller is registered with:

- controller path: `/navigation/menu`
- controller route name segment: `navigation_menu`

Expected generated route names:

- `ea_navigation_menu_index`
- `ea_navigation_menu_new`
- `ea_navigation_menu_create`
- `ea_navigation_menu_detail`
- `ea_navigation_menu_edit`
- `ea_navigation_menu_update`
- `ea_navigation_menu_delete`
- `ea_navigation_menu_import`
- `ea_navigation_menu_export`

Expected generated paths:

- `/ea/navigation/menu`
- `/ea/navigation/menu/new`
- `/ea/navigation/menu/create`
- `/ea/navigation/menu/{id}`
- `/ea/navigation/menu/{id}/edit`
- `/ea/navigation/menu/{id}/update`
- `/ea/navigation/menu/{id}/delete`
- `/ea/navigation/menu/import`
- `/ea/navigation/menu/export`

## Boundary

Navigating owns menu intent and admin navigation targets. Persistence and concrete business mutation remain host/admin responsibilities.

A full `AbstractCrudController` was not added because EasyAdmin CRUD controllers are Doctrine ORM entity CRUD controllers. Navigating currently does not own a Doctrine entity for YAML menu configuration, and W9 must not invent one.
