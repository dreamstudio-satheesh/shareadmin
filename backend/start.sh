#!/bin/bash
php artisan config:cache

# Start Laravel HTTP server in background
php artisan serve --host=0.0.0.0 --port=9000 &

# Start Redis subscriber and broadcast ticks in background
php artisan ticks:broadcast &

# Start Reverb WebSocket server in foreground (blocking)
php artisan reverb:start --debug
