#!/bin/sh
set -e

echo "Очищаем Laravel cache..."
rm -rf bootstrap/cache/*.php

echo "Очищаем runtime cache..."
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "Применяем миграции..."
php artisan migrate --force

echo "Заполняем справочник продуктов..."
php artisan db:seed --force

echo "Кэшируем production config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Запуск Apache..."
exec apache2-foreground