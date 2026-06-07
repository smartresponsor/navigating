# Navigating W20 — context related index roots

W20 narrows the meaning of the context zones.

## Contract

- `shell.context.top` is reserved for related **business** entities of the current resource.
- `shell.context.bottom` is reserved for related **system/admin** entities.
- Both zones expose only CRUD `index` targets.
- `shell.context.bottom` requires `ROLE_ADMIN` or `ROLE_SUPER_ADMIN`.
- No `new`, `edit`, `delete`, `import`, `export`, or resource action entries are allowed in these context slots.

## Example

For a vendor resource, the business context can expose related roots such as:

- `attachment.media.index`
- `tag.item.index`
- `address.location.index`

The admin context can expose related roots such as:

- `access.user.index`
- `governance.decision.index`
- `incident.report.index`
- `observability.metric.index`

This keeps the left navigation as root navigation and the context navigation as resource-adjacent navigation.
