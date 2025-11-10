# ---------- 1) Stage "vendor": installe les dépendances Composer ----------
FROM composer:2 AS vendor

WORKDIR /app

# Copie d'abord les fichiers composer pour profiter du cache Docker
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Puis on copie le reste du code et on optimise l'autoload
COPY . .
RUN composer dump-autoload -o

# ---------- 2) Stage final: PHP 8.2 + Apache ----------
FROM php:8.2-apache

# (Optionnel) fuseau horaire + env de prod
ENV TZ=Europe/Paris \
    APP_ENV=production \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Paquets système requis et extensions PHP utiles
RUN apt-get update && apt-get install -y --no-install-recommends \
      libpng-dev libjpeg-dev libfreetype6-dev \
      libzip-dev zip unzip \
      libicu-dev \
      git curl ca-certificates \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip intl opcache \
    && rm -rf /var/lib/apt/lists/*

# Active les modules Apache nécessaires
RUN a2enmod rewrite headers

# Configure le DocumentRoot sur /public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT%/public}/!g' /etc/apache2/apache2.conf

# Autorise les .htaccess (AllowOverride All) sur le DocumentRoot
RUN printf "<Directory ${APACHE_DOCUMENT_ROOT}>\n    AllowOverride All\n    Require all granted\n</Directory>\n" \
      > /etc/apache2/conf-available/allowoverride.conf \
    && a2enconf allowoverride

# Copie du code + vendor depuis le stage composer
WORKDIR /var/www/html
COPY --chown=www-data:www-data --from=vendor /app ./

# (Optionnel) droits d'écriture si ton app a /storage ou /var
# RUN chown -R www-data:www-data storage/ var/ || true

# Quelques réglages PHP de prod (opcache)
RUN { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=0"; \
      echo "opcache.validate_timestamps=0"; \
      echo "opcache.jit_buffer_size=0"; \
      echo "memory_limit=256M"; \
      echo "upload_max_filesize=16M"; \
      echo "post_max_size=16M"; \
    } > /usr/local/etc/php/conf.d/prod.ini

# IMPORTANT Railway: écouter sur $PORT (et pas 80)
# On réécrit au démarrage la conf Apache pour utiliser la variable d'environnement PORT
CMD bash -lc '\
  if [ -z "$PORT" ]; then export PORT=8080; fi; \
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf; \
  sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/*.conf; \
  exec apache2-foreground \
'

# EXPOSE est purement indicatif (Railway injecte le port)
EXPOSE 8080

# (Optionnel) healthcheck interne (utile en local)
HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD sh -lc 'curl -fsS "http://127.0.0.1:${PORT:-8080}/" >/dev/null || exit 1'
