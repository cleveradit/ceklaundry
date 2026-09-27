#!/bin/sh
set -eu
# Configuration is injected by Compose; an empty file avoids dotenv's missing-file warning.
[ -f .env ] || touch .env
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache
exec "$@"
