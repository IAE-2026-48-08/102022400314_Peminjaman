FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsqlite3-dev \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip bcmath opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (required by Laravel)
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files (including vendor - no internet download needed)
COPY . .

# Regenerate autoloader only (no download, uses existing vendor/)
RUN composer dump-autoload --optimize --no-interaction

# Configure Apache DocumentRoot to Laravel's public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>' \
    >> /etc/apache2/sites-available/000-default.conf

# Create SQLite database, run migrations and seeders
RUN touch database/database.sqlite \
    && php artisan migrate --force \
    && php artisan db:seed --force

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod 664 /var/www/html/database/database.sqlite

# Clear caches and generate Swagger docs
RUN php artisan config:clear \
    && php artisan cache:clear \
    && php artisan l5-swagger:generate

EXPOSE 80

CMD ["apache2-foreground"]
