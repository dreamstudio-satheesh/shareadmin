#!/bin/bash
php artisan config:cache

# Start Laravel HTTP server in background
php artisan serve --host=0.0.0.0 --port=9000 &

# Start Redis subscriber and broadcast ticks in background
php artisan ticks:broadcast &

# Start Laravel queue worker in background
php artisan queue:work --tries=3 &

# Start Reverb WebSocket server in foreground (blocking)
php artisan reverb:start # --debug


# [program:cron]
# command=cron -f
# autostart=true
# autorestart=true
# stdout_logfile=/dev/stdout
# stderr_logfile=/dev/stderr
