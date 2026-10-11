.PHONY: build install shell validate lint lint-fix test test-coverage integration integration-review-sample integration-review-cleanup clean-consumer audit hooks-install ci

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
	WORDPRESS_RETAIN_REVIEW_SAMPLE=0 tools/integration

WORDPRESS_REVIEW_PORT ?= 8080

integration-review-sample:
	WORDPRESS_RETAIN_REVIEW_SAMPLE=1 WORDPRESS_REVIEW_PORT=$(WORDPRESS_REVIEW_PORT) tools/integration

integration-review-cleanup:
	docker compose --project-name wordpress-sdk-integration-review --file docker-compose.integration.yml --file docker-compose.integration-review.yml down --volumes --remove-orphans

clean-consumer:
	tools/test-clean-consumer

audit:
	$(PHP) composer audit --locked

hooks-install:
	tools/install-git-hooks

ci:
	$(PHP) composer ci
