#!/bin/bash
php artisan config:cache

# Start Laravel HTTP server in background
php artisan serve --host=0.0.0.0 --port=9000 &

# Start Reverb WebSocket server in foreground
php artisan reverb:start
