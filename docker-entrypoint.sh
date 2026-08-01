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

php artisan storage:link || true

if [ "$CONTAINER_ROLE" = "queue-worker" ]; then
    echo "Starting Queue Worker..."
    exec php artisan queue:work --sleep=3 --tries=3
fi

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec php-fpm
