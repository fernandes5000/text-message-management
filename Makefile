.PHONY: setup start stop restart shell artisan migrate seed test tinker build dev logs

setup:
	docker compose build --no-cache
	docker compose up -d mysql redis mailhog
	sleep 10
	docker compose run --rm app composer create-project laravel/laravel /tmp/laravel --prefer-dist --quiet
	docker compose run --rm app bash -c "shopt -s dotglob && cp -rn /tmp/laravel/* /var/www/html/ && rm -rf /tmp/laravel"
	docker compose up -d
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan storage:link
	docker compose exec app npm install
	@echo "\n✓ Setup complete. Run 'make dev' to start the Vite dev server."

start:
	docker compose up -d

stop:
	docker compose down

restart:
	docker compose down && docker compose up -d

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

test-coverage:
	docker compose exec app php artisan test --coverage

tinker:
	docker compose exec app php artisan tinker

build:
	docker compose exec app npm run build

dev:
	docker compose exec app npm run dev

logs:
	docker compose logs -f

%:
	@:
