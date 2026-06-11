#!/bin/sh
set -e

# Framework caches depend on the runtime environment, so they are rebuilt on
# every container start instead of being baked into the image.
php artisan config:cache
php artisan route:cache
php artisan event:cache

# Run migrations only when explicitly enabled (one-off task or single node).
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
