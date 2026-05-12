# Stage 1: PHP Builder
FROM php:8.5-cli-bookworm as php-builder

ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www/html

# Install OS-level dependencies for PHP extensions and composer
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip gd bcmath pcntl intl exif

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files and install composer dependencies
COPY . .
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Stage 2: Node Builder
FROM node:20 as node-builder

WORKDIR /var/www/html

# Copy application files and install npm dependencies
COPY --from=php-builder /var/www/html .
RUN npm install
RUN npm run build

# Stage 3: Production Image (PHP-FPM)
FROM php:8.5-fpm-bookworm as production

ARG WWWGROUP
WORKDIR /var/www/html

# Install only necessary production OS dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    libicu-dev \
    gosu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for production
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip gd bcmath pcntl intl exif

# Setup sail user
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

# Copy built application files with vendor dependencies
COPY --from=php-builder /var/www/html .

# Copy built frontend assets
COPY --from=node-builder /var/www/html/public/build ./public/build

RUN mkdir -p /etc/php-fpm.d
COPY docker/sail/8.5/php-fpm.conf /etc/php-fpm.conf
COPY docker/sail/8.5/www.conf /etc/php-fpm.d/www.conf

USER root

COPY docker/sail/8.5/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

EXPOSE 9000

CMD ["php-fpm", "-y", "/etc/php-fpm.conf", "--nodaemonize"]
