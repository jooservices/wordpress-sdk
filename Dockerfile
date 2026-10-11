# PHP 8.5 CLI (Bookworm) — local + CI tooling image for jooservices/wordpress-sdk
FROM php:8.5.11-cli-bookworm@sha256:819080ed413631f6ec564184dd9acd08d9425bd863b9f8b5876b5ea2147d55e7

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

COPY --from=composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR /app

CMD ["php", "-v"]
