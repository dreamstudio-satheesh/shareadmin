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
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 storage bootstrap/cache 

# Setup cron
COPY docker/crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler && crontab /etc/cron.d/laravel-scheduler

# Copy and set start script
# COPY start.sh /start.sh
# RUN chmod +x /start.sh

# Expose PHP-FPM port for Caddy
EXPOSE 9000

# Start all Laravel services
CMD ["/start.sh"]
