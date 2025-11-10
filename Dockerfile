# ---------- 1) Stage "vendor": Composer ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload -o

# ---------- 2) Stage final: PHP 8.2 + Apache ----------
FROM php:8.2-apache

ENV TZ=Europe/Paris \
    APP_ENV=production \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Paquets + extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
      libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip libicu-dev \
      git curl ca-certificates \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip intl opcache \
    && rm -rf /var/lib/apt/lists/*

# Apache
RUN a2enmod rewrite headers
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT%/public}/!g' /etc/apache2/apache2.conf
RUN printf "<Directory ${APACHE_DOCUMENT_ROOT}>\n    AllowOverride All\n    Require all granted\n</Directory>\n" \
      > /etc/apache2/conf-available/allowoverride.conf \
    && a2enconf allowoverride

WORKDIR /var/www/html
COPY --chown=www-data:www-data --from=vendor /app ./

# (Décommente si ton app écrit dans ces dossiers)
# RUN chown -R www-data:www-data storage/ var/ || true

# Réglages PHP (prod)
RUN { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=0"; \
      echo "opcache.validate_timestamps=0"; \
      echo "memory_limit=256M"; \
      echo "upload_max_filesize=16M"; \
      echo "post_max_size=16M"; \
    } > /usr/local/etc/php/conf.d/prod.ini

# Adapter Apache pour écouter sur $PORT (injecté par Railway)
CMD bash -lc '\
  : "${PORT:=8080}"; \
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf; \
  sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/*.conf; \
  exec apache2-foreground \
'

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD sh -lc 'curl -fsS "http://127.0.0.1:${PORT:-8080}/" >/dev/null || exit 1'
