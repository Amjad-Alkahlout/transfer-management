#!/bin/sh

set -e

echo "======================================="
echo "Starting Laravel Application..."
echo "======================================="

echo "Waiting for MySQL..."

until php -r "
try {
    new PDO(
        'mysql:host=' . getenv('DB_HOST') .
        ';port=' . getenv('DB_PORT') .
        ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
"; do
    echo "MySQL is unavailable - sleeping..."
    sleep 2
done

echo "MySQL is ready."

if [ ! -f .env ]; then
    echo ".env file not found!"
    exit 1
fi

php artisan optimize:clear

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php-fpm
