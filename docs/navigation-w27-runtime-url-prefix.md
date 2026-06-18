# W28 — Namespace-owned runtime scope gate

This decision supersedes the W27 URL-prefix rule.

## Canonical rule

A navigation item is published only when its provider workspace is active in `APP_RUNTIME_SCOPE`.

Ownership is resolved in this order:

1. `namespace_provider`;
2. `namespace`;
3. group-level `namespace_provider` or `namespace` inheritance.

For an App namespace, the first segment after `App` is the runtime scope:

- `App\Cruding\Service\...` becomes `cruding`;
- `App\Vendoring\Service\...` becomes `vendoring`;
- `App\Interfacing\...` becomes `interfacing`.

The resulting value must occur exactly in `APP_RUNTIME_SCOPE`. Missing ownership fails closed.

## Non-inputs

Runtime publication does not infer component ownership from:

- URI prefixes;
- labels;
- `metadata.domain`;
- `APP_RUNTIME_ENTITY`.

Therefore `/payment/index` may be published under `cruding` when its actual namespace provider belongs to `App\Cruding`; the URI itself does not imply `paying`.

## Pipeline position

Namespace scope filtering runs in the visibility layer before view-model build and rendering. Role, request-scope, and environment visibility remain independent filters.
