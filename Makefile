CONSOLE := php -d memory_limit=512M bin/console
COMPOSER := composer

.DEFAULT_GOAL := help
.PHONY: help install jwt-keys clean db-reset db-reset-test tenant-fixtures \
        serve stop logs test stan lint lint-fix ci docker-up docker-down

help: ## Show this help.
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage: make \033[36m<target>\033[0m\n"} \
	     /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } \
	     /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

install: ## Install composer deps + generate JWT keypair.
	$(COMPOSER) install
	$(CONSOLE) lexik:jwt:generate-keypair --skip-if-exists

jwt-keys: ## (Re)generate JWT keypair (force overwrite).
	$(CONSOLE) lexik:jwt:generate-keypair --overwrite

clean: ## Clear and warm dev cache.
	$(CONSOLE) cache:clear --env=dev


db-reset: ## Drop, create, migrate Main + tenants, then load all fixtures.
	$(CONSOLE) doctrine:database:drop --connection=default --force --if-exists
	$(CONSOLE) doctrine:database:create --connection=default
	$(CONSOLE) doctrine:migrations:migrate --conn=default -n
	$(CONSOLE) doctrine:fixtures:load -n --group=main
	$(CONSOLE) tenant:database:create --all
	$(CONSOLE) tenant:migrations:migrate init -n
	$(MAKE) tenant-fixtures

db-reset-test: ## Same as db-reset but on the test environment (Main DB only).
	$(CONSOLE) doctrine:database:drop --connection=default --force --if-exists --env=test
	$(CONSOLE) doctrine:database:create --connection=default --env=test
	$(CONSOLE) doctrine:migrations:migrate --conn=default -n --env=test
	$(CONSOLE) doctrine:fixtures:load -n --group=main --env=test

tenant-fixtures: ## Load fixtures into every registered tenant DB.
	@for id in $$(PGPASSWORD=app psql -U app -h 127.0.0.1 -d app -t -A -c "SELECT id FROM tenant_db_config ORDER BY id"); do \
		echo "→ Loading fixtures into tenant $$id"; \
		$(CONSOLE) tenant:fixtures:load $$id -n; \
	done


serve: ## Start the Symfony local server (background).
	symfony server:start -d

stop: ## Stop the Symfony local server.
	symfony server:stop

logs: ## Tail the server log.
	tail -f var/log/dev.log


test: ## Run PHPUnit.
	vendor/bin/phpunit

stan: ## Run PHPStan analysis (level 8 with baseline).
	vendor/bin/phpstan analyse --memory-limit=1G

lint: ## Check code style (dry-run).
	vendor/bin/php-cs-fixer fix --dry-run --diff

lint-fix: ## Apply code style fixes.
	vendor/bin/php-cs-fixer fix

ci: lint stan test ## Full quality check: lint + stan + test.


docker-up: ## Start the Postgres container (compose.yaml).
	docker compose up -d

docker-down: ## Stop the Postgres container.
	docker compose down
