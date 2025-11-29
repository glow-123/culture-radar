FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy all application source code
COPY . /var/www/html/

# Remove unnecessary files from the container
RUN rm -rf /var/www/html/docker-compose* \
    /var/www/html/Dockerfile \
    /var/www/html/README* \
    /var/www/html/*.md \
    /var/www/html/*.pdf \
    /var/www/html/*.docx \
    /var/www/html/*.pptx \
    /var/www/html/desktop.ini

# Create cache directories
RUN mkdir -p /var/www/html/cache/events \
    /var/www/html/cache/weather \
    /var/www/html/cache/transport

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/cache

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
