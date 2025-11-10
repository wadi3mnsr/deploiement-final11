# -------- 1) Stage composer (facultatif si pas de composer.json) --------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress || true
COPY . .
RUN composer dump-autoload -o || true

# -------- 2) Image finale: PHP 8.2 + Apache --------
FROM php:8.2-apache

ENV APP_ENV=production \
    TZ=Europe/Paris

# Paquets + extensions PHP utiles
RUN apt-get update && apt-get install -y --no-install-recommends \
      libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip libicu-dev \
      ca-certificates curl git gettext-base \
  && docker-php-ext-configure gd --with-jpeg --with-freetype \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl opcache \
  && rm -rf /var/lib/apt/lists/*

# Apache: modules utiles
RUN a2enmod rewrite headers

# --- Détection auto du DocumentRoot (public/ prioritaire sinon racine)
#   Si /var/www/html/public existe -> DocumentRoot = .../public
#   Sinon -> DocumentRoot = /var/www/html
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN set -eux; \
    if [ -d /var/www/html/public ]; then export APACHE_DOCUMENT_ROOT=/var/www/html/public; fi; \
    : # placeholders

# On ajuste les confs Apache au runtime (pour tenir compte du PORT + DocumentRoot)
# 1) On garde des templates .template puis on injecte $PORT/$APACHE_DOCUMENT_ROOT au démarrage
RUN mv /etc/apache2/ports.conf /etc/apache2/ports.conf.template && \
    for f in /etc/apache2/sites-available/*.conf; do mv "$f" "$f.template"; done && \
    printf "<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n" \
      > /etc/apache2/conf-available/allowoverride.conf && a2enconf allowoverride

# Code + vendor
WORKDIR /var/www/html
COPY --from=vendor /app ./
# Si pas de composer, on aura copié le repo plus haut, c’est OK.

# Droits d’écriture (décommente si tu as ces répertoires)
# RUN chown -R www-data:www-data storage/ var/ runtime/ || true

# Réglages PHP (prod)
RUN { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=0"; \
      echo "opcache.validate_timestamps=0"; \
      echo "memory_limit=256M"; \
      echo "post_max_size=16M"; \
      echo "upload_max_filesize=16M"; \
    } > /usr/local/etc/php/conf.d/zz-prod.ini

# Entrypoint: injecte $PORT et APACHE_DOCUMENT_ROOT puis démarre Apache
CMD bash -lc '\
  : "${PORT:=8080}"; \
  : "${APACHE_DOCUMENT_ROOT:=/var/www/html}"; \
  if [ -d "/var/www/html/public" ]; then APACHE_DOCUMENT_ROOT="/var/www/html/public"; fi; \
  envsubst "\$PORT" < /etc/apache2/ports.conf.template > /etc/apache2/ports.conf; \
  for f in /etc/apache2/sites-available/*.conf.template; do \
    envsubst "\$PORT \$APACHE_DOCUMENT_ROOT" < "$f" > "${f%.template}"; \
  done; \
  sed -ri -e "s#DocumentRoot .*#DocumentRoot ${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/sites-available/*.conf; \
  exec apache2-foreground \
'

EXPOSE 8080

# Healthcheck (utile en local)
HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD sh -lc 'curl -fsS "http://127.0.0.1:${PORT:-8080}/" >/dev/null || exit 1'
