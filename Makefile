.PHONY: build install shell validate lint lint-fix test test-coverage integration clean-consumer audit hooks-install ci

DOCKER_COMPOSE ?= tools/ci/docker-compose
PHP = $(DOCKER_COMPOSE) run --rm php

build:
	$(DOCKER_COMPOSE) build

install: build
	$(PHP) composer install

shell:
	$(DOCKER_COMPOSE) run --rm php bash

validate:
	$(PHP) composer validate --strict

lint:
	$(PHP) composer lint

lint-fix:
	$(PHP) composer lint:fix

test:
	$(PHP) composer test

test-coverage:
	$(PHP) composer test:coverage

integration:
	tools/integration

clean-consumer:
	tools/test-clean-consumer

audit:
	$(PHP) composer audit --locked

hooks-install:
	$(PHP) composer hooks:install

ci:
	$(PHP) composer ci
