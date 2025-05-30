#!/bin/bash

# Exit on error
set -e

# Ensure Laravel caches are fresh
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions (optional, safe fallback)
chown -R www-data:www-data /var/www/html
chmod -R 775 storage bootstrap/cache

# Ensure PHP-FPM listens on 0.0.0.0:9000
sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# Start PHP-FPM in background
php-fpm -D

# Start queue worker
php artisan queue:work --tries=3 &

# Start ticks broadcaster
php artisan ticks:broadcast &

# Start Reverb WebSocket (blocking)
php artisan reverb:start

# Wait for all background jobs
wait


# [program:cron]
# command=cron -f
# autostart=true
# autorestart=true
# stdout_logfile=/dev/stdout
# stderr_logfile=/dev/stderr
