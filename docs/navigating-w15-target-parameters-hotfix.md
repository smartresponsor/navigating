# Navigating W15 target parameters hotfix

## Purpose

Fix runtime config validation for canonical CRUD menu links that use `target.parameters`.

## Decision

`target.parameters` is accepted as a readable alias for `target.params` in route targets.

The runtime value object still exposes normalized route parameters as `NavigationTarget::$params`.

## Scope

- No migrations.
- No route grammar rename.
- No EasyAdmin CRUD redesign.
- No hardcoded `/ea`.
