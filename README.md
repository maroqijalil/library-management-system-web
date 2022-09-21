# Library Management System
## Setup
1. `docker-compose build app`
2. `docker-compose up -d`
3. `docker-compose exec app compose install`
4. `docker-compose exec app php artisan key:generate`
5. `docker-compose exec app php artisan migrate`