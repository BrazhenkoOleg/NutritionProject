#!/bin/sh
set -e

echo "Удаляем старые кэши Render..."
rm -rf bootstrap/cache/*

echo "Применяем миграции и сидеры..."
php artisan migrate:fresh --force
php artisan db:seed --force

echo "Очищаем конфигурацию и view кэш..."
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "Запуск Apache..."
exec apache2-foreground
