# Navigating W10 — EasyAdmin CRUD with Doctrine SQLite

W10 turns the admin surface into native EasyAdmin CRUD backed by Doctrine ORM and SQLite.

## Decisions

- Back office URL prefix is not hardcoded in PHP.
- Default prefix is `ea` through `app.default_back_token`.
- Override it with `APP_BACK_TOKEN`.
- `/APP_BACK_TOKEN/*` is protected by `ROLE_ADMIN`.
- EasyAdmin dependency remains `^5.0`.
- Persistence is Doctrine ORM over SQLite for standalone mode.
- Admin code lives under `src/Controllers/Admin/`.
- CRUD execution uses `AbstractCrudController` for `NavigationItem`.
- Custom import/export are EasyAdmin CRUD actions, not custom Twig pages.
- Native EasyAdmin template `@EasyAdmin/page/content.html.twig` is used for action pages.

## Environment

```dotenv
APP_BACK_TOKEN=ea
DATABASE_URL=sqlite:///%kernel.project_dir%/var/navigation.sqlite
```

## Local apply commands

```powershell
composer update easycorp/easyadmin-bundle doctrine/doctrine-bundle doctrine/orm doctrine/dbal symfony/security-bundle --with-dependencies
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
composer qa
```

## Scope boundary

Navigating owns the admin navigation entry model and EA CRUD surface. It does not introduce a commercial route surface and does not add security/auth business flows beyond the `ROLE_ADMIN` guard for the back office prefix.
