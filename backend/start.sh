#!/bin/bash
set -e # Exit immediately if a command exits with a non-zero status

echo "Running pre-start commands..."

# Ensure Laravel caches are clear (runs as www-data)
# Use full path to artisan to ensure it's found
php /var/www/html/artisan config:clear
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear

echo "Starting background Laravel processes..."

# Start queue worker in the background
# Output (stdout/stderr) of background processes will go to Docker logs
php /var/www/html/artisan queue:work --tries=3 --timeout=3600 &
QUEUE_PID=$! # Store PID to potentially kill later

# # Start Reverb WebSocket in the background
# php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080 --debug &
# REVERB_PID=$! # Store PID

# # Start ticks broadcasting in the background
# php /var/www/html/artisan ticks:broadcast &
# TICKS_PID=$! # Store PID

# Trap signals to gracefully stop background processes when the container stops
trap "echo 'Stopping background processes...'; kill -TERM $QUEUE_PID $REVERB_PID $TICKS_PID; wait; echo 'All background processes stopped.'" SIGTERM SIGINT

echo "Starting PHP-FPM in foreground..."

# Start PHP-FPM in the foreground. This must be the last command.
# The -F flag is crucial for PHP-FPM to stay in the foreground in a Docker container.
exec php-fpm -F

# The 'wait' command is not strictly needed here after 'exec php-fpm -F'
# because php-fpm will take over PID 1 and keep the container alive.
# The trap will ensure background processes are killed if the container receives SIGTERM/SIGINT.