#!/usr/bin/env bash
# Brings a bare clone to a running application, then hands over to the command.
#
# Everything here is idempotent: the second start finds the work already done and
# skips it. Only the web container prepares the database — the queue worker and
# the websocket server wait for it rather than racing it.
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
READY_FLAG="storage/framework/.bootstrapped"

say() { printf '\033[0;36m[support]\033[0m %s\n' "$1"; }

wait_for_mysql() {
    say "waiting for the database…"
    until mysqladmin ping -h"${DB_HOST:-mysql}" -u"${DB_USERNAME:-sail}" -p"${DB_PASSWORD:-password}" --silent >/dev/null 2>&1; do
        sleep 2
    done
    say "database is up."
}

ensure_env_file() {
    if [ ! -f .env ]; then
        say "creating .env from .env.example"
        cp .env.example .env
    fi
}

# Needs vendor/, so it only runs once the dependencies are in place.
ensure_app_key() {
    if ! grep -qE '^APP_KEY=base64:' .env; then
        say "generating the application key"
        php artisan key:generate --force --no-interaction
    fi
}

ensure_dependencies() {
    if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
        say "installing the PHP dependencies (first run, this takes a minute)"
        composer install --no-interaction --prefer-dist --no-progress
    fi
}

ensure_assets() {
    if [ ! -d public/build ]; then
        say "building the front-end assets (first run)"
        npm ci --no-audit --no-fund --silent
        npm run build
    fi
}

prepare_application() {
    ensure_env_file
    ensure_dependencies
    ensure_app_key
    wait_for_mysql

    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
    chmod -R ug+rwx storage bootstrap/cache || true

    if [ ! -f "$READY_FLAG" ]; then
        say "preparing the database and the demo data"
        php artisan migrate:fresh --seed --force
        touch "$READY_FLAG"
    else
        php artisan migrate --force
    fi

    ensure_assets

    php artisan optimize:clear >/dev/null 2>&1 || true

    say "ready — http://localhost:${APP_PORT:-8080}"
}

case "$ROLE" in
    app)
        prepare_application
        ;;
    *)
        # Workers only need the code and a reachable database.
        until [ -f vendor/autoload.php ] && [ -f "$READY_FLAG" ]; do
            sleep 3
        done
        wait_for_mysql
        ;;
esac

exec "$@"
