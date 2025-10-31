FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    chmod -R 755 /var/www/html/database && \
    && chmod 666 /var/www/html/database/bilet-satis-veritabani.db \
    && mkdir -p /var/www/html/temp \
    && mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/cache \
    && chmod 777 /var/www/html/temp \
    && chmod 777 /var/www/html/logs \
    && chmod 777 /var/www/html/cache

EXPOSE 80

CMD ["apache2-foreground"]

