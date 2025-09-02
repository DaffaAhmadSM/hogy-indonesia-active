FROM php:8.4-cli-trixie

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    git \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_mysql exif pcntl bcmath gd xml iconv simplexml xmlreader intl soap zip
RUN docker-php-ext-configure intl

# Configure PHP
RUN sed -i -e "s/upload_max_filesize = .*/upload_max_filesize = 1G/g" \
    -e "s/post_max_size = .*/post_max_size = 1G/g" \
    -e "s/memory_limit = .*/memory_limit = 2G/g" \
    # enable opcache
    -e "s/;opcache.enable=.*/opcache.enable=1/g" \
    /usr/local/etc/php/php.ini-production \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /app
# Get latest Composer and install
COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

# npm and Node.js

