FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql mysqli gd zip

# Apache DocumentRoot vers /var/www/html (pas /public)
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT%/html}/!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

COPY . .

# Corriger Apache pour écouter le port Railway
CMD ["bash", "-lc", "sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT}>/g\" /etc/apache2/sites-available/*.conf && exec apache2-foreground"]

EXPOSE 8080
