# Library Management System

## Setup

### Natively

Requirememts:

- PHP Version 7.2.5 and its base extensions
- Composer 1.6.5 (Recomended), exaclty newest version is okay
- MySQL

Steps:

1. `composer install`
2. `php artisan key:generate`
3. `php artisan migrate`

In case: Several installed PHP version (replace version to the version `7.2`):

1. `/usr/bin/php<version> /usr/local/bin/composer install`
2. `/usr/bin/php<version> artisan key:generate`
3. `/usr/bin/php<version> artisan migrate`

### Using Docker

Requirememts:

- Docker
- Docker Compose

Steps:

1. `docker compose build app`
2. `docker compose up -d`
3. `docker compose exec app composer install`
4. `docker compose exec app php artisan key:generate`
5. `docker compose exec app php artisan migrate`

## Terminate
`docker compose down`

## Code Smell Tools

This is required dev dependency of this laravel project. So, its funcionality will be included after `composer install` ran. 

### PHPMD ([reference](https://winnietech.com/quick-check-of-your-laravel-project-using-php-mess-detector/))

Usage:

`vendor/bin/phpmd app html ./phpmd-ruleset.xml > phpmd.html`

### Larastan ([reference](https://github.com/nunomaduro/larastan/tree/1.x))

Usage:

`./vendor/bin/phpstan analyse`

### Laravel-Enlightn ([reference](https://www.laravel-enlightn.com/docs/getting-started/installation.html#requirements))

Usage:

`php artisan enlightn`

or

`/usr/bin/php<version> artisan enlightn`
