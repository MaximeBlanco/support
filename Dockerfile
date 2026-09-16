# The image the whole stack runs on — app, queue worker and websocket server.
#
# It is deliberately self-contained: a fresh clone has no vendor/ directory, so
# the build cannot depend on anything Composer would have installed. Cloning and
# running `docker compose up -d` has to be enough.
FROM php:8.5-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip \
        libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        default-mysql-client \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl bcmath gd exif pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
