FROM openswoole/swoole:25.2-php8.4-alpine

LABEL maintainer="Workspace Environment Quality API"

ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    postgresql-client \
    redis \
    supervisor \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    pcntl \
    zip \
    mbstring \
    xml \
    bcmath \
    intl \
    gd \
    exif

# Install Redis PHP extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Create workspaceapi user
RUN addgroup -g ${GROUP_ID} workspaceapi \
    && adduser -u ${USER_ID} -G workspaceapi -s /bin/bash -D workspaceapi

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=workspaceapi:workspaceapi . .

# Set permissions for storage and cache directories
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R workspaceapi:workspaceapi storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy entrypoint script and make it executable
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown workspaceapi:workspaceapi /usr/local/bin/entrypoint.sh

# Switch to workspaceapi user
USER workspaceapi

# Expose port 80
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
