# Navigating W14 Doctrine config hotfix

Doctrine ORM configuration is kept compatible with current DoctrineBundle configuration keys.

## Fixed

- Removed `doctrine.orm.auto_generate_proxy_classes`.
- Removed invalid `doctrine.orm.controller_resolver`.
- Kept entity-first SQLite mapping under `doctrine.orm.mappings.Navigating`.
- Kept migration-free database flow.

## Runtime flow

```powershell
php bin/console cache:clear
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
```

No migration files are introduced by this patch.
