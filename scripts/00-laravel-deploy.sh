#!/usr/bin/env bash
echo "Caching config and routes..."
php artisan config:cache
php artisan route:cache

echo "Linking storage..."
php artisan storage:link

echo "Running migrations and seeders..."
php artisan migrate --force
php artisan db:seed --force