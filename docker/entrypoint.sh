#!/bin/sh
set -e

APP_DIR="/var/www/html"

cd "$APP_DIR"

# Create the Laravel project only when it does not exist yet
if [ ! -f artisan ]; then
  echo "Laravel project not found. Creating a new application..."

  TMP_DIR="$(mktemp -d)"
  composer create-project laravel/laravel "$TMP_DIR"

  cp -a "$TMP_DIR"/. "$APP_DIR"/
  rm -rf "$TMP_DIR"
fi

# Install Composer dependencies when they are missing
if [ ! -d vendor ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction
fi

# Copy the environment file when it is missing
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "Creating .env from .env.example..."
  cp .env.example .env
fi

# Configure Redis connection for Docker Compose
if [ -f .env ]; then
  sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env || true
  sed -i 's/^REDIS_PORT=.*/REDIS_PORT=6379/' .env || true
  sed -i 's/^REDIS_CLIENT=.*/REDIS_CLIENT=phpredis/' .env || true
  sed -i 's/^REDIS_PREFIX=.*/REDIS_PREFIX=/' .env || true
  sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env || true
  sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env || true
  sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env || true

  grep -q '^REDIS_HOST=' .env || echo 'REDIS_HOST=redis' >> .env
  grep -q '^REDIS_PORT=' .env || echo 'REDIS_PORT=6379' >> .env
  grep -q '^REDIS_CLIENT=' .env || echo 'REDIS_CLIENT=phpredis' >> .env
  grep -q '^REDIS_PREFIX=' .env || echo 'REDIS_PREFIX=' >> .env
  grep -q '^SESSION_DRIVER=' .env || echo 'SESSION_DRIVER=file' >> .env
  grep -q '^CACHE_STORE=' .env || echo 'CACHE_STORE=file' >> .env
  grep -q '^QUEUE_CONNECTION=' .env || echo 'QUEUE_CONNECTION=sync' >> .env
fi

# Ensure writable Laravel directories
mkdir -p storage bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

# Generate the application key when it is missing
if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
  echo "Generating application key..."
  php artisan key:generate --force
fi

# Clear Laravel caches before starting
if [ -f artisan ]; then
  echo "Clearing Laravel caches..."
  php artisan optimize:clear || true
fi

# Use the PHP built-in server directly to avoid router file permission issues
# that can happen with `php artisan serve` on mounted volumes.
exec php -S 0.0.0.0:8000 -t public public/index.php
