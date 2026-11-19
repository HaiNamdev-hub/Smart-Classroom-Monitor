# ============================================================
# Dockerfile – Smart Classroom Monitor Backend (Laravel 12)
# Deploy trên Render.com (Free tier)
# PHP 8.2 + Nginx + PostgreSQL (Neon) + Cloudinary
# ============================================================

FROM php:8.2-fpm-alpine AS base

# Cài đặt các extension PHP cần thiết
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        zip \
        unzip \
        libpng-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        gd \
        bcmath \
        opcache \
        pcntl

# Cài Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Cấu hình PHP upload limits
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Cấp quyền thư mục tạm và log cho Nginx khi chạy dưới user www-data
RUN mkdir -p /var/lib/nginx/tmp/client_body \
             /var/lib/nginx/tmp/proxy \
             /var/lib/nginx/tmp/fastcgi \
             /tmp/nginx_client_body \
             /var/log/nginx \
    && chown -R www-data:www-data /var/lib/nginx /tmp/nginx_client_body /var/log/nginx \
    && chmod -R 775 /var/lib/nginx /tmp/nginx_client_body /var/log/nginx

# ============================================================
# Cấu hình Nginx
# ============================================================
COPY docker/nginx.conf /etc/nginx/nginx.conf

# ============================================================
# Copy source code
# ============================================================
WORKDIR /var/www/html

COPY . .

# Cài đặt dependencies PHP (không cần dev packages khi production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Tạo thư mục storage và cấp quyền
RUN mkdir -p storage/logs \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ============================================================
# Supervisord – chạy song song PHP-FPM và Nginx
# ============================================================
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ============================================================
# Script khởi động: migrate + seed (nếu cần) + start server
# ============================================================
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]
