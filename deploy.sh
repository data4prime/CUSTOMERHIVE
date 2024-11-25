docker-compose up -d --build
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed