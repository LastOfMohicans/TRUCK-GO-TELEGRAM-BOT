FROM php:8.3-fpm

# Install dependencies
RUN apt-get update && apt-get install -fyqq \
    bash curl nano wget rsync ca-certificates openssl ssh git tzdata openntpd tar \
    libxrender1 fontconfig libc6 \
    postgresql-client gnupg binutils-gold autoconf \
    g++ gcc gnupg libgcc1 make \
    libpq-dev libicu-dev libpng-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && chmod 755 /usr/bin/composer

# Install nodejs
RUN wget http://nodejs.org/dist/v20.13.1/node-v20.13.1-linux-x64.tar.gz \
    && tar -C /usr/local --strip-components 1 -xzf node-v20.13.1-linux-x64.tar.gz \
    && rm node-v20.13.1-linux-x64.tar.gz

# Install PHP extensions
RUN docker-php-ext-install bcmath pdo pdo_pgsql pgsql gd zip \
    && docker-php-ext-enable pgsql

# Install xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Add XDebug configuration
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html
