# PHP 8.3.12 Backend Dockerfile
FROM php:8.3.12-fpm

# Install system dependencies including nginx
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    libicu-dev \
    gnupg \
    unixodbc-dev \
    ca-certificates \
    wget \
    nginx \
    && rm -rf /var/lib/apt/lists/*

# Install Microsoft SQL Server drivers (Alternative method)
RUN wget -qO- https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && echo "deb [arch=amd64] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install PHP SQL Server extension
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy ALL application files first (including composer files)
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create uploads directory
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Generate application key if not exists
RUN php artisan key:generate --force || true

# Run post-install scripts
RUN composer run-script post-autoload-dump || true

# Configure Nginx for Laravel
RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /var/www/html/public; \
    index index.php; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/sites-available/default

# OPcache: re-read PHP files when changed (important when rebuilding/mounting code in Docker)
RUN echo "opcache.validate_timestamps=1" > /usr/local/etc/php/conf.d/opcache-revalidate.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache-revalidate.ini

# Expose port
EXPOSE 80

# Start both Nginx and PHP-FPM
CMD service nginx start && php-fpm
