#!/bin/bash

echo "Fixing Symfony permissions..."

cd /var/www/html || exit

mkdir -p var/cache var/log

chown -R www-data:www-data var
chmod -R 775 var

rm -rf var/cache/*

echo "Permissions fixed."
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction