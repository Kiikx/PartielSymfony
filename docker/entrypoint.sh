#!/bin/sh
set -e

git config --global --add safe.directory /var/www/html

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

mkdir -p var/cache var/log
chmod -R u+rwX,g+rwX var

exec "$@"
