#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

# Sync fresh assets to the shared volume for Nginx
if [ -d "/var/www/public_source" ]; then
    echo "Syncing public assets to shared volume..."
    cp -a /var/www/public_source/. /var/www/public/
fi

# Cache Laravel configurations
echo "Caching Laravel configuration..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Execute the main command (e.g., php-fpm)
exec "$@"
