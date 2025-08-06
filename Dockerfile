FROM php:8.2-apache

# Instalar dependencias del sistema de forma más eficiente
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar solo los archivos necesarios primero
COPY composer.json composer.lock* ./

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar el resto de archivos del proyecto
COPY . .

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Cambiar usuario actual a www
USER www-data

# Exponer puerto 80
EXPOSE 80

CMD ["apache2-foreground"] 