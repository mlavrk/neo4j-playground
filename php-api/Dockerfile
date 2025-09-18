# php-api/Dockerfile
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite and headers
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json ./

# Install PHP dependencies (this will create composer.lock and vendor/)
RUN composer install --no-dev --optimize-autoloader

# Copy application code (but preserve vendor directory)
COPY index.php ./
COPY src/ ./src/
COPY test-interface.html ./
COPY 000-default.conf ./

# Copy Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80