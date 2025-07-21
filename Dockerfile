FROM php:8.3-apache

# Ativa o mod_rewrite para suportar o htaccess com URLs amigáveis
RUN a2enmod rewrite

# Altera o Apache para permitir uso de htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Extensões 
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-install -j$(nproc) \
    mbstring \
    gd \
    curl \
    zip \
    intl \
    bcmath \
    xml \
    soap \
    fileinfo \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

