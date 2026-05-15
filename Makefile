DC                  := docker compose

# Defaults (can be overridden by environment)
DB_DATABASE         ?= technical_test
DB_USERNAME         ?= app
DB_PASSWORD         ?= secret

# ── Project Setup ───────────────────────────────────────────────────────────────

.PHONY: setup
setup: up-build install db-schema db-seed

# ── Dependencies ───────────────────────────────────────────────────────────────

.PHONY: install
install:
	$(DC) exec app composer install

# ── Quality ─────────────────────

.PHONY: tests
tests:
	$(DC) exec app ./vendor/bin/phpunit

.PHONY: behat
behat:
	$(DC) exec app ./vendor/bin/behat

.PHONY: tests-coverage
tests-coverage:
	$(DC) exec -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-html coverage-report/

.PHONY: analyse
analyse:
	$(DC) exec app php -d memory_limit=512M ./vendor/bin/phpstan analyse

.PHONY: format
format:
	$(DC) exec app ./vendor/bin/php-cs-fixer fix

.PHONY: format-check
format-check:
	$(DC) exec app ./vendor/bin/php-cs-fixer fix --dry-run

# ── Docker ─────────────────────────────────────────────────────────────────────

.PHONY: up
up:
	$(DC) up -d

.PHONY: up-build
up-build:
	$(DC) up -d --build

.PHONY: down
down:
	$(DC) down

.PHONY: build
build:
	$(DC) build

.PHONY: shell
shell:
	$(DC) exec app sh

.PHONY: logs
logs:
	$(DC) logs -f app

.PHONY: restart
restart:
	$(DC) restart app

.PHONY: ps
ps:
	$(DC) ps

# ── Database helpers ──────────────────────────────────────────────────────────

.PHONY: db-schema
db-schema:
	# Re-apply database schema.sql into the running MySQL service
	$(DC) exec -T mysql sh -lc 'mysql -u "$${DB_USERNAME:-$(DB_USERNAME)}" -p"$${DB_PASSWORD:-$(DB_PASSWORD)}" "$${DB_DATABASE:-$(DB_DATABASE)}"' < database/schema.sql

.PHONY: db-seed
db-seed:
	# Load fixtures.sql (assumes file exists at database/fixtures.sql)
	$(DC) exec -T mysql sh -lc 'mysql -u "$${DB_USERNAME:-$(DB_USERNAME)}" -p"$${DB_PASSWORD:-$(DB_PASSWORD)}" "$${DB_DATABASE:-$(DB_DATABASE)}"' < database/fixtures.sql

.PHONY: regenerate-seed
regenerate-seed:
	$(DC) exec app php database/generate_fixtures.php > database/fixtures.sql

.PHONY: help
help:
	@echo "Available targets:"
	@echo "  setup              → Build, start, and init DB (schema + fixtures)"
	@echo "  up, up-build, down, build, shell, logs, restart, ps"
	@echo "  install, tests, tests-coverage, analyse, format, format-check"
	@echo "  db-schema, db-seed"
