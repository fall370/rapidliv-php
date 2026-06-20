FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

COPY . /var/www/html/

RUN mkdir -p /var/www/html/public/uploads/livreurs \
    /var/www/html/public/uploads/products \
    /var/www/html/public/uploads/services \
    /var/www/html/storage/livreurs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads /var/www/html/storage

EXPOSE 80
