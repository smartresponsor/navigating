# Navigating W22 — canonical runtime engine

W22 removes the temporary W21 runtime wrapper model and makes the canonical navigation surface the runtime engine itself.

## Engine flow

```text
NavigationShellProvideService
  -> NavigationConfigValidateService
  -> NavigationConfigNormalizeService
  -> NavigationVisibilityFilterService
  -> NavigationTreeBuildService
  -> NavigationTargetResolveService
  -> NavigationShellView / NavigationGroupView / NavigationItemView
```

Twig never searches routes. Twig asks the navigation surface for a shell, a group, or rendered group HTML.

## Canonical folders

```text
src/Service/Navigation/Normalize
src/Service/Navigation/Validate
src/Service/Navigation/Filter
src/Service/Navigation/Build
src/Service/Navigation/Resolve
src/Service/Navigation/Provide
src/Service/Navigation/Render
src/Service/Navigation/Merge
src/Service/Twig/Navigation
src/Model/Navigation/View
```

## Removed old physical grammar

The old flat runtime classes were removed from `src/Service/Navigation`:

```text
NavigationRuntimeProvider
NavigationTreeBuilder
NavigationTargetResolver
NavigationConfigNormalizer
NavigationConfigValidator
NavigationRoleVisibilityFilter
NavigationTemplateDataProvider
NavigationResponseProvider
NavigationShellPayloadProvider
NavigationShellChromeMerger
TwigNavigationRenderer
RequestAttributeNavigationRoleProvider
```

The old root interfaces were removed as well:

```text
NavigationRuntimeProviderInterface
NavigationShellPayloadProviderInterface
NavigationRendererInterface
NavigationRequestRoleProviderInterface
```

## Public Twig surface

```twig
{% set shell = navigating_shell() %}
{% set group = navigating_group('shell.left.middle') %}

{{ navigating_render('shell.left.middle') }}
```

## No migrations

W22 does not add Doctrine migrations and does not change the entity-first SQLite design.
