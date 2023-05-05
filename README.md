# start project:
composer install
make .env
php artisan key:generate
php artisan migrate
php artisan jwt:generate-certs
php artisan jwt:secret
