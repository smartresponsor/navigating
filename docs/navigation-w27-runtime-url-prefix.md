# W27 — Runtime URL-prefix gate

Navigating treats the installed App runtime lists as an exact allowlist for local component links.

## Canonical rule

For every local navigation link, Navigating resolves the final URL and extracts the first path segment.

Examples:

- `/vendor/index` requires `vendor`;
- `/catalog/index` requires `catalog`;
- `/interface/index` requires `interface`.

The required token must occur exactly in either `APP_RUNTIME_SCOPE` or `APP_RUNTIME_ENTITY`. Semantic aliases and `metadata.domain` do not activate a different URL prefix.

Therefore:

- `category` does not activate `/catalog/index`;
- `product` does not activate `/merchandise/index`;
- `interfacing` does not activate `/interface/index`;
- `vendor` does not activate `/vendor-extra/index`.

## Boundary behavior

- Same-host absolute URLs are checked as local links.
- External-host URLs are not treated as App component links and pass this gate.
- `/` is the host root and has no component prefix.
- Empty or unresolved link targets fail closed.
- Actions and widgets without link targets are unaffected by URL-prefix activation.

Role, request-scope, and environment visibility remain separate filters. Runtime activation is applied after target resolution, before a link enters the shell view model.
