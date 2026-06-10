#!/bin/bash

# Exit on error
set -e

echo "Running production optimizations..."

# 1. Optimize Composer Autoloader
echo "Optimizing Composer Autoloader..."
composer dump-autoload -o --apcu || composer dump-autoload -o

# 2. Clear all previous caches
echo "Clearing old caches..."
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan event:clear

# 3. Cache configuration, routes, and views for production
echo "Building Laravel production cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Done! The application is fully optimized for production."
