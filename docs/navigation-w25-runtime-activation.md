# W25 — Runtime activation-aware navigation publication

Navigating applies a deployment activation ceiling before building shell view models.

## Runtime sources

- `APP_RUNTIME_SCOPE` supplies active architectural component tokens.
- `APP_RUNTIME_ENTITY` supplies active business entity tokens.

The values are read through Symfony environment bindings. Navigating does not read `.env.prod` directly and does not mutate App runtime configuration.

## Decision order

An item is published only when all configured visibility conditions and its selected deployment activation axis pass:

1. `enabled` and `visible`;
2. role visibility;
3. request navigation-scope visibility;
4. environment visibility;
5. deployment entity activation for entity-mapped business domains, otherwise deployment component activation for explicitly scope-mapped system domains.

Request attributes can narrow navigation but cannot activate a domain absent from the App deployment runtime.

## Mapping and axis selection

Activation starts from item-level `metadata.domain` and selects one runtime axis:

1. An explicit `runtime_activation.entity_by_domain` mapping takes precedence and is checked against `APP_RUNTIME_ENTITY`.
2. Otherwise, an explicit `runtime_activation.scope_by_domain` mapping is checked against `APP_RUNTIME_SCOPE`.
3. Otherwise, the normalized domain is checked as an entity token. This keeps unknown business domains fail-closed without incorrectly treating them as architectural component scopes.

Labels, route names, paths, and repository-name transformations are not inspected at runtime.

Items without `metadata.domain` remain outside the deployment activation gate. This preserves structural shell locations and static legal/help links that do not represent a deployable domain.
