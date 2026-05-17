#!/bin/sh
set -e

echo "Проверяем Laravel public/index.php..."
ls -la /var/www/html/public
test -f /var/www/html/public/index.php

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

echo "Показываем маршруты..."
php artisan route:list

echo "Запуск Apache..."
exec apache2-foreground