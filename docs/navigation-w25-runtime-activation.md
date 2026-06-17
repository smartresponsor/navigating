# W25 — Runtime activation-aware navigation publication

Navigating now applies a deployment activation ceiling before building shell view models.

## Runtime sources

- `APP_RUNTIME_SCOPE` supplies active component tokens.
- `APP_RUNTIME_ENTITY` supplies active business entity tokens.

The values are read through Symfony environment bindings. Navigating does not read `.env.prod` directly and does not mutate App runtime configuration.

## Decision order

An item is published only when all configured conditions pass:

1. `enabled` and `visible`;
2. role visibility;
3. request navigation-scope visibility;
4. environment visibility;
5. deployment component activation;
6. deployment entity activation.

Request attributes can narrow navigation but cannot activate a component absent from the App deployment runtime.

## Mapping

Activation requirements are resolved from explicit `runtime_activation.scope_by_domain` and `runtime_activation.entity_by_domain` maps. Labels, route names, paths, and repository-name transformations are not used for runtime inference.

Items without a mapped domain remain outside the deployment activation gate. This preserves structural shell locations, static legal/help links, and component-owned actions that do not represent another deployable component.
