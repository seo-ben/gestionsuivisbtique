FROM serversideup/php:8.4-fpm-nginx

# Variables d'environnement pour Laravel en production
ENV AUTORUN_ENABLED=true
ENV PHP_OPCACHE_ENABLE=1

WORKDIR /var/www/html

# Copier le code avec les bonnes permissions
COPY --chown=999:999 . /var/www/html

# Installation des dépendances Composer pour la production
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8080
