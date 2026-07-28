.PHONY: setup dev lint typecheck test test-e2e test-e2e-ci verify build migrate seed smoke

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
	@if php -r "exit(PHP_OS_FAMILY === 'Windows' ? 0 : 1);" 2>/dev/null; then \
		echo "SKIP: Browser tests require pest-plugin-browser v4.4+ on Windows (upstream bug #1517)."; \
		echo "       Run in CI (Linux) or use Laravel Dusk locally."; \
	else \
		php artisan test tests/Browser; \
	fi

test-e2e-ci:
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
