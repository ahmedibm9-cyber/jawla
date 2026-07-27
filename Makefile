.PHONY: setup dev lint typecheck test test-e2e verify build migrate seed smoke

setup:
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	php artisan migrate --force
	npm install
	npm run dev

dev:
	npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
		"php artisan serve" \
		"php artisan queue:listen --tries=1 --timeout=0" \
		"php artisan pail --timeout=0" \
		"npm run dev" \
		--names=server,queue,logs,vite --kill-others

lint:
	vendor/bin/pint --test

typecheck:
	vendor/bin/phpstan analyse

test:
	php artisan test --testsuite=Unit,Feature

test-e2e:
	php artisan test tests/Browser

build:
	npm run build

migrate:
	php artisan migrate --force

seed:
	php artisan db:seed --class=DemoSeeder

smoke:
	@echo "Running smoke tests..."
	php artisan route:list --columns=method,uri > /dev/null
	php artisan config:cache > /dev/null
	php artisan view:cache > /dev/null
	@echo "Smoke tests passed."

verify: lint typecheck test build
	@echo "All checks passed."
