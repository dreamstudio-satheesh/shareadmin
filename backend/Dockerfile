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
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd pcntl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
# These files will initially be owned by root
COPY . .

# Install PHP dependencies
# This runs as root, so files in vendor/ will be owned by root
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize

# Set up directories and permissions
# Create storage & cache directories (if not already existing from COPY)
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Set ownership for the entire application directory to www-data:www-data
# This MUST be done as root, before switching to USER www-data.
# This will cover all copied files (like public/assets) and composer-generated files (like vendor/).
RUN chown -R www-data:www-data /var/www/html

# Set specific writable permissions for storage and cache for Laravel
# www-data needs to write here, and the group often needs write access too.
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Setup cron
COPY docker/crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler && crontab /etc/cron.d/laravel-scheduler

# Copy and set start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Expose PHP-FPM port for Caddy
EXPOSE 9000

# Drop privileges to www-data (never run as root for the application)
USER www-data

# Start all Laravel services
CMD ["/start.sh"]