FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/
RUN chmod 644 /var/www/html/*.php

# Create logs directory
RUN mkdir -p /var/www/html/logs && \
    chown www-data:www-data /var/www/html/logs

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
