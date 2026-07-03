#!/bin/sh
set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

mkdir -p var/cache var/log
chmod -R u+rwX,g+rwX var

exec "$@"
