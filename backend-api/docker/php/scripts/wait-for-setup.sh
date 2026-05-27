#!/usr/bin/env sh
set -eu

cd /var/www/html

until [ -f vendor/autoload.php ] && [ -f storage/app/setup.done ]; do
  echo "Waiting for backend setup..."
  sleep 2
done

exec "$@"
