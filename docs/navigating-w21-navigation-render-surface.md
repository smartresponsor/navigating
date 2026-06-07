# Navigating W21 — navigation render surface

W21 adds the canonical runtime surface that lets Twig consume Navigating without knowing route maps or platform navigation grammar.

## Canonical ownership

Navigating owns the navigation model:

- shell locations;
- group payloads;
- item visibility;
- active state;
- route/path/action/widget target semantics;
- render-ready view models.

Twig does not discover routes. Twig asks Navigating for a shell or a group and renders the returned view model.

## Added structure

```text
src/Service/Twig/Navigation/NavigationTwigExtension.php

src/Service/Navigation/Provide/NavigationShellProvideService.php
src/Service/Navigation/Provide/NavigationGroupProvideService.php
src/Service/Navigation/Resolve/NavigationTargetResolveService.php
src/Service/Navigation/Render/NavigationRenderService.php

src/ServiceInterface/Navigation/Provide/NavigationShellProvideServiceInterface.php
src/ServiceInterface/Navigation/Provide/NavigationGroupProvideServiceInterface.php
src/ServiceInterface/Navigation/Resolve/NavigationTargetResolveServiceInterface.php
src/ServiceInterface/Navigation/Render/NavigationRenderServiceInterface.php

src/Model/Navigation/View/NavigationShellView.php
src/Model/Navigation/View/NavigationGroupView.php
src/Model/Navigation/View/NavigationItemView.php
src/Model/Navigation/View/NavigationTargetView.php
```

## Twig API

```twig
{% set shell = navigating_shell() %}
{% set leftBusiness = navigating_group('shell.left.middle') %}
{{ navigating_render('shell.left.middle') }}
```

The `navigating_shell()` function returns all canonical shell groups.
The `navigating_group(location)` function returns one slot-ready group.
The `navigating_render(location)` function returns minimal semantic HTML for simple hosts.

## Boundary

Templates should not call platform routes directly for navigations. This is not canonical:

```twig
{{ path('vendor.profile.index') }}
```

This is canonical:

```twig
{% for item in navigating_group('shell.left.middle').items %}
    <a href="{{ item.href }}">{{ item.label }}</a>
{% endfor %}
```

## Compatibility

W21 keeps the existing runtime provider and legacy renderer services. It adds the render surface on top of the accepted W20 line.

W16 route renaming is not included.
Migrations are not added.
