FROM php:8.3-apache

# Instalación de dependencias del sistema necesarias para GD, ZIP, etc.
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configura ext-gd y otras extensiones php
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql zip gd intl mbstring xml bcmath

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Ajustar DocumentRoot a la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar composer (desde la imagen oficial) para usar composer durante build
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia solo composer files para aprovechar cache de Docker
COPY composer.json composer.lock ./

# Instalar dependencias de PHP antes de copiar el resto (cacheable)
RUN composer install --no-interaction --optimize-autoloader --prefer-dist

# Copiar el resto de la aplicación
COPY . .

# Permisos correctos para storage y cache
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Comando por defecto (arranca Apache en foreground)
CMD ["apache2-foreground"]