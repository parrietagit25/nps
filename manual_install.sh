#!/bin/bash

echo "🔧 Instalación manual de dependencias..."

# Verificar si estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    echo "❌ Error: No se encontró composer.json"
    exit 1
fi

# Instalar Composer si no está disponible
if ! command -v composer &> /dev/null; then
    echo "📦 Instalando Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Limpiar cache de Composer
echo "🧹 Limpiando cache de Composer..."
composer clear-cache

# Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Verificar que vendor/ existe
if [ -d "vendor" ]; then
    echo "✅ Dependencias instaladas correctamente"
    ls -la vendor/
else
    echo "❌ Error: No se pudo instalar las dependencias"
    exit 1
fi

echo "🎉 Instalación completada!" 