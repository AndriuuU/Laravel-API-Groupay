#!/usr/bin/env bash

echo "Instalando dependencias Composer..."
composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

echo "Limpiando cachés..."
php artisan config:cache
php artisan route:cache

echo "Ejecutando migraciones..."
php artisan migrate --force
