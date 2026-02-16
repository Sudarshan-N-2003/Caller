FROM php:8.2-apache

# Install PostgreSQL support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean

# Enable rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Tell Docker which port we expect
EXPOSE 10000

# Start Apache on Render PORT dynamically
CMD ["sh", "-c", "sed -i \"s/80/${PORT}/g\" /etc/apache2/ports.conf && sed -i \"s/:80/:${PORT}/g\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
