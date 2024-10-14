up: ## Start all or c=<name> containers in foreground
	docker compose up -d

build: ## Build
	docker compose up -d --build
start: ## Start all or c=<name> containers in background
	docker compose start

stop: ## Stop all or c=<name> containers
	docker compose stop

#status: ## Show status of containers
#	docker compose ps

#status all: ## Show status of containers
#	docker compose ps -a

restart: ## Restart all or c=<name> containers
	docker compose stop
	docker compose start

down: ## Remove all containers
	docker compose down

logs: ## Show logs for all or c=<name> containers
	docker compose logs --tail=100 -f
