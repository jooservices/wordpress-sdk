# PHP 8.5 CLI (Bookworm) — local + CI tooling image for jooservices/wordpress-sdk
FROM php:8.5.9-cli-bookworm@sha256:b3154b925899c55cca2885581c74cd9966484dc7469a36584353a6c8c26bbde0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) zip \
    && pecl install pcov-1.0.12 \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR /app

CMD ["php", "-v"]
