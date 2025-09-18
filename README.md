### Description
Test playground to play with neo4j and php api. Generated mostly by Claude.

### Start the containers

```bash
docker-compose up -d
```

rebuild
```bash
docker-compose down
docker rmi neo4j-playground-php-api
docker-compose up -d --build
```

after first run, rebuild or some changes to composer.json we need to run composer install manually
```bash
make composerInstall
# or
docker exec php-social-api composer install
```

### Access the services

Neo4j Browser: http://localhost:7474 (username: neo4j, password: password123)

PHP API: http://localhost:8080

Test Interface: http://localhost:8080/test-interface.html