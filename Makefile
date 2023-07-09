up:
	docker-compose up -d

init: up
	docker-compose exec app composer install
	docker-compose exec app php bin/console doctrine:migration:migrate

down:
	docker-compose down

test:
	APP_ENV=test php bin/console cache:clear
	php bin/console --no-interaction --env=test doctrine:migration:migrate first
	php bin/console --no-interaction --env=test doctrine:migration:migrate
	php bin/console --no-interaction --env=test doctrine:fixtures:load
	APP_ENV=test php bin/phpunit
