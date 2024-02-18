# ----------------------------------------------------------------------------
# Make target args
# ----------------------------------------------------------------------------

ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

# ----------------------------------------------------------------------------
# Configuration
# ----------------------------------------------------------------------------
NGINX_CONTAINER = "tfc_http"
PHP_CONTAINER = "tfc_app"
APP_ENV ?= dev

USER := $(shell id -u):$(shell id -g)
DOCKER_EXEC = docker exec -e APP_ENV=$(APP_ENV) -e PHP_IDE_CONFIG=serverName=$(NGINX_CONTAINER) -it $(PHP_CONTAINER)
DOCKER_EXEC_NO_INTERACTIVE = docker exec -e PHP_IDE_CONFIG=serverName=$(NGINX_CONTAINER) $(PHP_CONTAINER)
DOCKER_COMPOSE = docker compose
DOCKER_COMPOSE_XDEBUG = APP_ENV=$(APP_ENV) $(DOCKER_COMPOSE) -f compose.yaml -f compose.override.xdebug.yaml
SYMFONY = $(DOCKER_EXEC) php bin/console
TESTS = $(DOCKER_EXEC) php bin/phpunit

# ----------------------------------------------------------------------------
# Global
# ----------------------------------------------------------------------------

.DEFAULT_GOAL = help
.PHONY: help docker docker-compose docker-up docker-down docker-up-with-xdebug docker-build docker-build-no-cache \
		docker-logs bash composer composer-install composer-require composer-require-dev composer-remove symfony \
		symfony-shell symfony-cache-clear symfony-cache-warmup server-logs app-logs tests

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | \
		sed -e 's/\[32m##/[33m/'

# ----------------------------------------------------------------------------
# Docker
# ----------------------------------------------------------------------------
docker:
	@$(DOCKER_COMPOSE) ps

docker-compose: ## Define and run multi-container applications with Docker (same as docker compose [options] <command>)
	@USER=${USER} $(DOCKER_COMPOSE) $(or $(ARGS),ps)

docker-up: ## Creates and starts containers
	@make --no-print-directory docker-compose "up --detach"

docker-down: ## Stop and remove containers
	@make --no-print-directory docker-compose "down --remove-orphans"

docker-up-with-xdebug: ## Creates and starts containers using customized configuration for Linux
	@$(DOCKER_COMPOSE_XDEBUG) up --detach

docker-build: ## Build or rebuild services using the local cache
	@$(DOCKER_COMPOSE) build $(ARGS)

docker-build-no-cache: ## Build or rebuild services (avoids using local cache)
	@make docker-build -- --no-cache

docker-logs: ## Show logs for containers (default: all)
	@make --no-print-directory docker-compose logs $(ARGS)

bash: ## Starts a Bash session on APP container (PHP)
	@$(DOCKER_EXEC) bash

# ----------------------------------------------------------------------------
# Composer
# ----------------------------------------------------------------------------

composer: ## Runs composer on APP container
	@USER=${USER} $(DOCKER_EXEC) composer $(or $(ARGS),--version)

composer-install: ## Install packages from composer.json on APP container
	@make --no-print-directory composer install $(ARGS)

composer-require: ## Install a composer package on APP container
	@make --no-print-directory composer require $(ARGS)

composer-require-dev: ## Install a composer packages for "dev" on APP container
	@make --no-print-directory composer require --dev $(ARGS)

composer-remove: ## Remove a composer package from APP container
	@make --no-print-directory composer remove $(ARGS)

# ----------------------------------------------------------------------------
# Symfony
# ----------------------------------------------------------------------------

symfony: ## Runs the Symfony console (e.g. make symfony -- about)
	@PHP_IDE_CONFIG=${PHP_IDE_CONFIG} $(SYMFONY) $(ARGS)

symfony-shell: ## Runs the psysh shell
	@make --no-print-directory symfony psysh

symfony-cache-clear:
	@make --no-print-directory symfony cache:clear

symfony-cache-warmup:
	@make --no-print-directory symfony cache:warmup

server-logs: ## Outputs the webserver logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

app-logs: ## Outputs the content of the log file specified (e.g. make app-logs -- prod.log)
	@$(DOCKER_EXEC) tail -f var/log/$(or $(ARGS),dev.log)

# ----------------------------------------------------------------------------
# Tests
# ----------------------------------------------------------------------------

tests:
	@$(TESTS) $(ARGS)

# ----------------------------------------------------------------------------
# Make helper
# ----------------------------------------------------------------------------

%: # black hole to prevent extra args warning
	@:
