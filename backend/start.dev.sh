#!/bin/bash
set -e

echo "🔧 Running production setup..."

# Cache Laravel config and routes for performance
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache
php /var/www/html/artisan event:cache

echo "🚀 Starting background Laravel processes..."

# Start queue worker (you may want to supervise this with a tool like Supervisor in non-Docker setups)
php /var/www/html/artisan queue:work --tries=3 --timeout=3600 --quiet & 
QUEUE_PID=$!

# Start Reverb WebSocket
php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080 & 
REVERB_PID=$!

# Start ticks broadcaster
php /var/www/html/artisan ticks:broadcast & 
TICKS_PID=$!

# Gracefully stop background processes
trap "echo '🛑 Stopping...'; kill -TERM $QUEUE_PID $REVERB_PID $TICKS_PID; wait; echo '✅ Shutdown complete.'" SIGTERM SIGINT

echo "🧠 Starting PHP-FPM..."
exec php-fpm -F
