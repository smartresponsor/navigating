.PHONY: navigation-install navigation-backup navigation-restore navigation-manifest-write navigation-manifest-verify navigation-manifest-restore navigation-schema-update navigation-schema-safe navigation-rebuild navigation-qa

PHP ?= php
COMPOSER ?= composer
BACKUP ?=

navigation-install:
	$(PHP) bin/console navigation:database:update
	$(PHP) bin/console navigation:database:import-config

navigation-backup:
	$(PHP) bin/console navigation:backup:create $(BACKUP)

navigation-restore:
	@test -n "$(BACKUP)" || (echo "BACKUP=<path> is required" && exit 1)
	$(PHP) bin/console navigation:backup:restore "$(BACKUP)" --force

navigation-manifest-write:
	$(PHP) bin/console navigation:manifest:write

navigation-manifest-verify:
	$(PHP) bin/console navigation:manifest:verify

navigation-manifest-restore:
	$(PHP) bin/console navigation:manifest:restore --force

navigation-schema-update:
	$(PHP) bin/console navigation:database:update

navigation-schema-safe:
	$(COMPOSER) navigation:schema:safe

navigation-rebuild:
	$(PHP) bin/console navigation:database:rebuild --force $(if $(BACKUP),--backup-path="$(BACKUP)",)

navigation-qa:
	$(COMPOSER) validate
	$(PHP) bin/console lint:container
	$(PHP) bin/console doctrine:schema:validate
	$(COMPOSER) qa
