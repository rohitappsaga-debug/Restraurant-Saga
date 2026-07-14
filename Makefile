# ─────────────────────────────────────────────
#  RestaurantSaga — Makefile
#  make install   → first-time local setup (migrate, seed, storage link)
#  make up        → start local dev stack (docker-compose.yml)
#  make test      → run the full test suite
#  make deploy    → build image on server, safe migrate, restart prod stack
# ─────────────────────────────────────────────

COMPOSE       := docker compose -f docker-compose.yml
PROD_COMPOSE  := docker compose -f docker-compose.prod.yml --env-file .env.production
PROD_ENV      := .env.production
APP           := $(COMPOSE) exec app
APP_PROD      := $(PROD_COMPOSE) exec app

.DEFAULT_GOAL := help

# ── Colours ──────────────────────────────────
CYAN  := \033[0;36m
RESET := \033[0m

# ─────────────────────────────────────────────
#  LOCAL
# ─────────────────────────────────────────────

.PHONY: up
up: ## Start local dev stack
	$(COMPOSE) up -d
	@echo ""
	@echo "$(CYAN)App:     http://localhost:8080$(RESET)"
	@echo "$(CYAN)Reverb:  ws://localhost:8081$(RESET)"

.PHONY: down
down: ## Stop and remove local containers
	$(COMPOSE) down

.PHONY: restart
restart: down up ## Restart local stack

.PHONY: build
build: ## Rebuild local Docker images without cache
	$(COMPOSE) build --no-cache

.PHONY: install
install: up ## First-time setup: composer, key, migrate, seed, assets
	@echo "Waiting for services to be ready..."
	@sleep 5
	$(APP) composer install --no-interaction
	$(APP) php artisan key:generate --ansi
	$(APP) php artisan migrate --force
	$(APP) php artisan db:seed --force
	$(APP) php artisan storage:link
	$(APP) npm install
	$(APP) npm run build
	$(APP) php artisan optimize:clear
	@echo ""
	@echo "$(CYAN)Done! App running at http://localhost:8080$(RESET)"

.PHONY: test
test: ## Run the full test suite (uses the pgsql 'testing' database)
	$(COMPOSE) exec pgsql psql -U restaurant -d restaurantsaga -tc "SELECT 1 FROM pg_database WHERE datname = 'testing'" | grep -q 1 || \
		$(COMPOSE) exec pgsql psql -U restaurant -d restaurantsaga -c "CREATE DATABASE testing OWNER restaurant;"
	$(APP) php artisan test

.PHONY: assets
assets: ## Rebuild frontend assets (Vite)
	$(APP) npm run build

.PHONY: logs
logs: ## Tail logs for all local services (Ctrl+C to stop)
	$(COMPOSE) logs -f

.PHONY: logs-app
logs-app: ## Tail logs for the app container only
	$(COMPOSE) logs -f app

.PHONY: shell
shell: ## Open a shell inside the local app container
	$(APP) bash

.PHONY: migrate
migrate: ## Run migrations locally
	$(APP) php artisan migrate

.PHONY: fresh
fresh: ## Fresh migrate + seed locally (⚠ destroys data)
	$(APP) php artisan migrate:fresh --seed

.PHONY: tinker
tinker: ## Open Artisan tinker locally
	$(APP) php artisan tinker

.PHONY: psql
psql: ## Open a psql shell on the local database
	$(COMPOSE) exec pgsql psql -U restaurant -d restaurantsaga

.PHONY: queue-restart
queue-restart: ## Restart the queue worker
	$(COMPOSE) restart queue

.PHONY: ps
ps: ## Show local container status
	$(COMPOSE) ps

# ─────────────────────────────────────────────
#  PRODUCTION — build & push image (optional)
# ─────────────────────────────────────────────

.PHONY: image
image: ## Build & push image to Docker Hub  (usage: make image TAG=1.0.0)
	$(eval TAG ?= $(shell git rev-parse --short HEAD))
	docker buildx build --platform linux/amd64,linux/arm64 \
		-t appsagaio/restaurantsaga:$(TAG) \
		-t appsagaio/restaurantsaga:latest \
		-f Dockerfile.production --push .

# ─────────────────────────────────────────────
#  PRODUCTION — deploy
# ─────────────────────────────────────────────

.PHONY: deploy
deploy: _check-prod-env ## Build image on server, safe migrate, restart prod stack
	$(PROD_COMPOSE) build
	@echo "$(CYAN)→ Running migrations safely...$(RESET)"
	$(PROD_COMPOSE) run --rm app php artisan migrate --force && \
		( \
			echo "$(CYAN)→ Migrations OK — restarting containers...$(RESET)"; \
			$(PROD_COMPOSE) up -d --force-recreate; \
			$(PROD_COMPOSE) exec app php artisan optimize:clear; \
			$(PROD_COMPOSE) exec app php artisan optimize; \
			echo ""; \
			echo "$(CYAN)Deployed successfully.$(RESET)"; \
		) || \
		( \
			echo ""; \
			echo "Migration failed — old version still running safely."; \
			exit 1; \
		)

.PHONY: prod-up
prod-up: _check-prod-env ## Start production stack without rebuilding image
	$(PROD_COMPOSE) up -d

.PHONY: prod-down
prod-down: _check-prod-env ## Stop production stack
	$(PROD_COMPOSE) down

.PHONY: prod-ps
prod-ps: _check-prod-env ## Show production container status
	$(PROD_COMPOSE) ps

.PHONY: prod-logs
prod-logs: _check-prod-env ## Tail production logs (Ctrl+C to stop)
	$(PROD_COMPOSE) logs -f

.PHONY: prod-shell
prod-shell: _check-prod-env ## Open a shell inside the production app container
	$(APP_PROD) sh

.PHONY: prod-migrate
prod-migrate: _check-prod-env ## Run migrations on production
	$(APP_PROD) php artisan migrate --force

# ─────────────────────────────────────────────
#  HELPERS
# ─────────────────────────────────────────────

.PHONY: _check-prod-env
_check-prod-env:
	@if [ ! -f "$(PROD_ENV)" ]; then \
		echo ""; \
		echo "Missing $(PROD_ENV). Set it up first:"; \
		echo "  cp .env.example $(PROD_ENV)  # then fill in secrets"; \
		echo ""; \
		exit 1; \
	fi

.PHONY: help
help: ## Show this help
	@echo ""
	@echo "Usage: make [target]"
	@echo ""
	@echo "LOCAL"
	@grep -E '^[a-z][a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| grep -v 'prod\|deploy\|image\|_check\|build' \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""
	@echo "PRODUCTION"
	@grep -E '^(deploy|image|prod)[a-zA-Z_-]*:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""
