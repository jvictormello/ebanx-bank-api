FROM php:8.3-cli-alpine

# Install system dependencies required by Laravel and PHP extensions
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    bash \
    curl \
    git \
    libzip-dev \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-install \
    bcmath \
    pcntl \
    zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer from the official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
