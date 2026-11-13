#!/usr/bin/env bash
set -euo pipefail

echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage..."
php artisan storage:link --force || true

echo "==> Running migrations..."
php artisan migrate --force

if [ "${SEED_ON_DEPLOY:-false}" = "true" ]; then
  echo "==> Seeding database (SEED_ON_DEPLOY=true)..."
  php artisan db:seed --force
fi

echo "==> Release complete."
