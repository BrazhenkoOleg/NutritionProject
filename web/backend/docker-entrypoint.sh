#!/bin/sh
set -e

echo "Подготавливаем Laravel storage..."

mkdir -p storage/app/public/analyses
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

echo "Создаём public storage link..."

if [ ! -L public/storage ]; then
    rm -rf public/storage
    php artisan storage:link
fi

echo "Очищаем Laravel cache..."
rm -rf bootstrap/cache/*.php

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