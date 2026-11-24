#!/usr/bin/env bash
echo "Running composer..."
composer install --no-dev --working-dir=/var/www/html

echo "Caching config and routes..."
php artisan config:cache
php artisan route:cache

echo "Linking storage..."
php artisan storage:link

echo "Running migrations..."
php artisan migrate --force