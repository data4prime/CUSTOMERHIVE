## CUSTOMERHIVE 

docker-compose up --build -d

docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed