#!/usr/bin/env sh
set -e

# Run migrations if we are starting the web server
if [ "$1" = 'apache2-foreground' ]; then
    echo "[entrypoint] Running Doctrine migrations..."
    if [ "$(id -u)" -eq 0 ] && id www-data >/dev/null 2>&1; then
        su -s /bin/sh www-data -c "php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"
    else
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    fi
fi

exec "$@"
