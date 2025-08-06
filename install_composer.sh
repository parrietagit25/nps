#!/bin/bash

# Script para instalar Composer y dependencias
echo "Instalando Composer y dependencias..."

# Verificar si composer está instalado
if ! command -v composer &> /dev/null; then
    echo "Composer no está instalado. Instalando..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Instalar dependencias
echo "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Instalación completada!" 