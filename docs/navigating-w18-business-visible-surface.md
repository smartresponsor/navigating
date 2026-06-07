# Navigating W18 — business-visible navigation surface

W18 returns the component to the original Navigating responsibility: make platform navigation visible and useful before every backend surface is fully implemented.

## Scope

This patch reshapes `config/navigation.yaml` into a business-visible shell map:

- left workspace navigation;
- left business navigation;
- left admin/platform navigation;
- context CRUD shortcuts;
- main toolbar actions;
- right-side toggles;
- right-side tools;
- right-side filters;
- footer system/support/legal links.

The uploaded `platform-route-map-id-slug-with-cruding-grammar` package is used as a route-map hint. Selected route names and paths are recorded in:

```text
config/platform/navigation/business-visible.routes.yaml
```

## Design decision

Platform route-map entries are parser contracts, not guaranteed native Symfony routes inside this standalone Navigating component. Therefore W18 uses `path` targets for cross-platform navigation entries and records the canonical route name in `metadata.route_name`.

Example:

```yaml
catalog_product:
  type: link
  label: Catalog products
  path: /catalog/product/index
  metadata:
    route_name: catalog.product.index
```

This keeps the navigation renderable even before the target component has registered the native Symfony route.

## CRUD boundary

Navigation CRUD entries remain business-visible but still modelled as navigation targets. Navigating does not become the executor for all platform CRUD routes.

```text
Navigation owns: visible shell slots, navigation intent, labels, icons, route/path targets, visibility metadata.
Cruding/EasyAdmin/host app owns: actual business execution.
```

## Surfaces introduced

```text
shell.left.top       workspace/dashboard/search
shell.left.middle    commerce, finance, intelligence
shell.left.bottom    admin/platform controls
shell.context.top    current resource badge
shell.context.middle CRUD shortcuts
shell.main.toolbar   page-level actions
shell.right.top      toggles
shell.right.tool     tools/widgets
shell.right.filter   filter actions
shell.footer.*       system/support/legal
```

## No migrations

W18 does not add migrations and does not change the entity-first DB design.
