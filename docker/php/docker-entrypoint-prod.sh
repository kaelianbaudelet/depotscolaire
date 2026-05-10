#!/usr/bin/env sh
set -e

# Run migrations if we are starting the web server
if [ "$1" = 'apache2-foreground' ]; then
    echo "[entrypoint] Clearing cache..."
    if [ "$(id -u)" -eq 0 ] && id www-data >/dev/null 2>&1; then
        su -s /bin/sh www-data -c "php bin/console cache:clear --no-interaction"
        echo "[entrypoint] Running Doctrine migrations..."
        su -s /bin/sh www-data -c "php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"
        su -s /bin/sh www-data -c "php bin/console doctrine:fixtures:load --append --no-interaction"
    else
        php bin/console cache:clear --no-interaction
        echo "[entrypoint] Running Doctrine migrations..."
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
        php bin/console doctrine:fixtures:load --append --no-interaction
    fi
fi

exec "$@"
