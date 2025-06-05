#!/bin/bash
set -e

# Fix permissions (for development convenience)
# chown -R www-data:www-data /var/www/html
# chmod -R 775 storage bootstrap/cache

# Ensure Laravel caches are clear in development
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Make sure PHP-FPM listens on all interfaces
sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# Start PHP-FPM (in foreground for container)
exec php-fpm

# Start queue worker
 php artisan queue:work --tries=3 &

# Start Reverb WebSocket (blocking)
 php artisan reverb:start --debug &

# Start ticks broadcasting
php artisan ticks:broadcast &



# Wait for all background jobs
wait


# [program:cron]
# command=cron -f
# autostart=true
# autorestart=true
# stdout_logfile=/dev/stdout
# stderr_logfile=/dev/stderr
