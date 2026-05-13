COMPOSER ?= $(shell which composer)

.PHONY: install
install:
	$(PHP) $(COMPOSER) install

.PHONY: tests
tests:
	./vendor/bin/phpunit
	./vendor/bin/behat

.PHONY: format
format:
	./vendor/bin/php-cs-fixer fix