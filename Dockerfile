FROM php:8.3-apache

# ─── Extensions système nécessaires ───────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ─── Extensions PHP requises par Symfony ──────────────────────────────────────
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ─── Activer mod_rewrite Apache (obligatoire pour Symfony) ────────────────────
RUN a2enmod rewrite headers

# ─── OPcache config (performances en dev) ─────────────────────────────────────
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# ─── PHP config dev ───────────────────────────────────────────────────────────
RUN echo "upload_max_filesize=32M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=32M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/custom.ini

# ─── Répertoire de travail ────────────────────────────────────────────────────
WORKDIR /var/www/html

# ─── Copier la config Apache ──────────────────────────────────────────────────
COPY docker/conf/apache/symfony.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
