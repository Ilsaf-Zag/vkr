#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "Waiting for database..."
attempt=0
until php -r '
try {
    new PDO(
        "pgsql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD")
    );
    exit(0);
} catch (Throwable $exception) {
    exit(1);
}
'; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 60 ]; then
    echo "Database is unavailable."
    exit 1
  fi
  sleep 2
done

mkdir -p \
  bootstrap/cache \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan migrate --seed --force
php artisan storage:link || true
php artisan optimize:clear

date -Iseconds > storage/app/setup.done
chown -R www-data:www-data storage bootstrap/cache

echo "Backend setup completed."
