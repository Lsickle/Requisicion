# Usa PHP 8.3 con Apache
FROM php:8.3-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Copia archivos
COPY . /var/www/html

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependencias de Laravel
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Permisos para storage y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expone el puerto
EXPOSE 80

CMD ["apache2-foreground"]
# Usa PHP 8.3 con Apache
FROM php:8.3-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Copia archivos
COPY . /var/www/html

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependencias de Laravel
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Permisos para storage y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expone el puerto
EXPOSE 80

CMD ["apache2-foreground"]
