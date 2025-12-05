#!/usr/bin/env sh
set -e

composer_flags="--prefer-dist --no-progress --no-interaction"

prepare_symfony_var() {
    if [ "$(id -u)" -eq 0 ] && id www-data >/dev/null 2>&1; then
        mkdir -p var/cache var/log
        chown -R www-data:www-data var
    fi
}

ensure_composer_dependencies() {
    # Only install when dependencies are missing or outdated.
    if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
        echo "[entrypoint] Installing/updating Composer dependencies..."
        if [ "$(id -u)" -eq 0 ] && id www-data >/dev/null 2>&1; then
            su -s /bin/sh www-data -c "composer install ${composer_flags}"
        else
            composer install ${composer_flags}
        fi
    else
        echo "[entrypoint] Composer dependencies already up to date."
    fi
}

prepare_symfony_var

if [ "${SKIP_COMPOSER_INSTALL:-0}" != "1" ]; then
    ensure_composer_dependencies
else
    echo "[entrypoint] Skipping Composer install (SKIP_COMPOSER_INSTALL=${SKIP_COMPOSER_INSTALL})."
fi

exec "$@"
