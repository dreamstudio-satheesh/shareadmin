FROM php:8.3-fpm

# PHP custom settings
RUN echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/99-custom.ini \
    && echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/99-custom.ini

# System dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    bash \
    git \
    curl \
    zip \
    unzip \
    cron \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd pcntl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- IMPORTANT FIXES START HERE ---

# Make sure PHP-FPM listens on all interfaces (MUST run as root, during build)
# This prevents 'Operation not permitted' errors for www.conf
RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files (These will be owned by root initially)
COPY . .

# Install PHP dependencies
# Composer runs as root here, so files in vendor/ will be owned by root initially
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize

# Set up directories and permissions (MUST run as root, during build)
# Create necessary directories
RUN mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache
# Set ownership for the entire application directory to www-data
# This ensures all copied files (like public/assets) and composer-generated files (like vendor/)
# are owned by www-data BEFORE the container runs as www-data.
RUN chown -R www-data:www-data /var/www/html
# Set specific writable permissions for storage and cache for Laravel
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- IMPORTANT FIXES END HERE ---

# Setup cron (The crontab is installed, but cron itself won't be started by this start.sh.
# If you need cron jobs, you'll need a separate strategy or a process manager later.)
COPY docker/crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler && crontab /etc/cron.d/laravel-scheduler

# Copy and set start script (the new one provided above)
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Expose PHP-FPM port
EXPOSE 9000
# EXPOSE 8080 # Uncomment if Reverb is exposed directly to the host

# Drop privileges to www-data (this is where the script's user context is set)
USER www-data

# Start all Laravel services via start.sh
CMD ["/start.sh"]