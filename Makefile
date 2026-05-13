DC          := docker compose

# ── Dependencies ───────────────────────────────────────────────────────────────

.PHONY: install
install:
	$(DC) exec app composer install

# ── Quality (run inside the PHPcontainer) ─────────────────────

.PHONY: tests
tests:
	$(DC) exec app ./vendor/bin/phpunit
	$(DC) exec app ./vendor/bin/behat

.PHONY: behat
behat:
	$(DC) exec app ./vendor/bin/behat

.PHONY: tests-coverage
tests-coverage:
	$(DC) exec -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-html tests/coverage-report/

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
