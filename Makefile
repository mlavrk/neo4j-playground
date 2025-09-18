up:
	docker-compose up -d

down:
	docker-compose down

rebuild:
	docker-compose down && \
    docker rmi neo4j-playground-php-api && \
    docker-compose up -d --build

composerInstall:
	docker exec php-social-api composer install