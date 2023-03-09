FROM php:7.4-fpm

COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install --yes --no-install-recommends \
        git

RUN groupadd appuser \
    && useradd -m -g appuser -s /bin/bash appuser
USER appuser:appuser

WORKDIR /var/www