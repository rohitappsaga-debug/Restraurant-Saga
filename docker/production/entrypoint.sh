#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

# Cache Laravel configurations
echo "Caching Laravel configuration..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Execute the main command (e.g., php-fpm)
exec "$@"
