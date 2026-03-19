

# Use the official PHP image.
# https://hub.docker.com/_/php
FROM php:8.0-apache

# Install PostgreSQL driver
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql pgsql

# Configure PHP for Cloud Run.
# Precompile PHP code with opcache.
RUN docker-php-ext-install -j "$(nproc)" opcache
RUN set -ex; \
    { \
        echo "; Cloud Run enforces memory & timeouts"; \
        echo "memory_limit = -1"; \
        echo "max_execution_time = 0"; \
        echo "; File upload at Cloud Run network limit"; \
        echo "upload_max_filesize = 32M"; \
        echo "post_max_size = 32M"; \
        echo "; Configure Opcache for Containers"; \
        echo "opcache.enable = On"; \
        echo "opcache.validate_timestamps = Off"; \
        echo "; Configure Opcache Memory (Application-specific)"; \
        echo "opcache.memory_consumption = 32"; \
    } > "$PHP_INI_DIR/conf.d/cloud-run.ini"

# Copy in custom code from the host machine.
WORKDIR /var/www/html
COPY . ./
