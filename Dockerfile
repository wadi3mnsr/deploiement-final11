# PHP + Apache for FlexVTC
FROM php:8.2-apache

# 1) Installer dépendances système
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl nano \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev libonig-dev libssl-dev pkg-config \
 && rm -rf /var/lib/apt/lists/*

# 2) Extensions PHP nécessaires
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql zip gd intl opcache

# 3) Extension MongoDB via PECL
RUN pecl install mongodb \
 && docker-php-ext-enable mongodb

# 4) Apache : activer mod_rewrite
RUN a2enmod rewrite

# 5) Installer Composer (global)
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# 6) Config PHP (timezone + opcache)
RUN { \
      echo 'date.timezone=Europe/Paris'; \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=1'; \
      echo 'opcache.jit_buffer_size=64M'; \
      echo 'opcache.memory_consumption=192'; \
      echo 'opcache.max_accelerated_files=10000'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# 7) Répertoire de travail
WORKDIR /var/www/html

# 8) Copie du code (optionnel car tu montes les volumes avec docker-compose)
COPY ./public/ /var/www/html/
