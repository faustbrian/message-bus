compose_command = docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php85

build:
	docker-compose build

shell: build
	$(compose_command) bash

destroy:
	docker-compose down -v

composer: build
	$(compose_command) composer install

lint: build
	$(compose_command) composer lint

refactor: build
	$(compose_command) composer refactor

test: build
	$(compose_command) composer test

test\:lint: build
	$(compose_command) composer test:lint

test\:refactor: build
	$(compose_command) composer test:refactor

test\:type-coverage: build
	$(compose_command) composer test:type-coverage

test\:types: build
	$(compose_command) composer test:types

test\:unit: build
	$(compose_command) composer test:unit
