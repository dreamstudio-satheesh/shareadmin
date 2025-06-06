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
    gnupg \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd pcntl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ─── Install Node.js (Latest LTS) ────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

# PHP-FPM config
RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize

# ─── Frontend Build (Vite or Laravel Mix) ────────────────────────
RUN if [ -f package.json ]; then \
    npm install && npm run build; \
    fi

# Fix permissions
RUN mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Setup cron
COPY docker/crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler && crontab /etc/cron.d/laravel-scheduler

# Copy and set start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 9000

USER www-data

CMD ["/start.sh"]
