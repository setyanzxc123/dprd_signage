# ─────────────────────────────────────────────────────────────────
# Stage 1: Composer dependencies
# ─────────────────────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --ignore-platform-reqs

# ─────────────────────────────────────────────────────────────────
# Stage 2: App image
# ─────────────────────────────────────────────────────────────────
FROM php:8.2-apache

# Install PHP extensions yang dibutuhkan CI4
RUN apt-get update && apt-get install -y \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        cron \
        curl \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        intl \
        zip \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Konfigurasi PHP untuk production
RUN echo "date.timezone = Asia/Makassar" >> /usr/local/etc/php/conf.d/timezone.ini \
 && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini

# Set DocumentRoot ke /public (CI4 standard)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Konfigurasi Apache — izinkan .htaccess
RUN echo '<Directory "${APACHE_DOCUMENT_ROOT}">\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/dprd-signage.conf \
 && a2enconf dprd-signage

WORKDIR /var/www/html

# Salin source code
COPY . .

# Salin vendor dari stage 1
COPY --from=vendor /app/vendor ./vendor

# Salin cron job
COPY docker/cron/wa-notif /etc/cron.d/wa-notif
RUN chmod 0644 /etc/cron.d/wa-notif

# Permission untuk writable dan uploads
RUN mkdir -p writable/logs writable/cache writable/session writable/uploads public/uploads/media \
 && chown -R www-data:www-data writable public/uploads \
 && chmod -R 775 writable public/uploads

# Entrypoint script (start Apache + cron)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
