FROM php:8.3-apache

# ─── Extensions système ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ─── Extensions PHP ───────────────────────────────────────────────────────────
RUN docker-php-ext-install \
    pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ─── Apache ───────────────────────────────────────────────────────────────────
RUN a2enmod rewrite headers

# ─── PHP config ───────────────────────────────────────────────────────────────
RUN echo "opcache.enable=1\nopcache.validate_timestamps=1\nopcache.revalidate_freq=0" \
        >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "upload_max_filesize=32M\npost_max_size=32M\nmemory_limit=256M\nmax_execution_time=60" \
        >> /usr/local/etc/php/conf.d/custom.ini

# ─── Répertoire de travail (DOIT être défini avant tout COPY/RUN app) ─────────
WORKDIR /var/www/html

# ─── Dépendances Composer (layer mis en cache si composer.json intact) ─────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# ─── Code source complet (scripts Symfony disponibles pour les recettes) ──────
COPY . .

# ─── Exécuter les scripts Composer post-install (cache:clear, etc.) ───────────
RUN composer run-script post-install-cmd --no-dev || true

# ─── Config Apache ────────────────────────────────────────────────────────────
COPY docker/conf/apache/symfony.conf /etc/apache2/sites-available/000-default.conf

COPY fix-permissions.sh /usr/local/bin/fix-permissions.sh
RUN chmod +x /usr/local/bin/fix-permissions.sh

EXPOSE 80