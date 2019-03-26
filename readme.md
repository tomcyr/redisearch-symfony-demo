Symfony RediSearch demo
=======================

This is Symfony4 demo for Full-Text search with RediSearch and redisearch-php.

https://oss.redislabs.com/redisearch/

http://www.ethanhann.com/redisearch-php/

How to run this demo
 
- Run docker-compose

```
docker-compose up
```

- Run composer

```
docker-composer exec php bash
composer install
```

- Create indexes with sample data
```
php bin/console app:import-recipes
php bin/console app:import-bikes
```

- Open url http://symfony.local:8088

Enjoy!
