#!/bin/bash

# NPS Project Deployment Script
# Este script despliega el proyecto en el servidor

echo "🚀 Iniciando despliegue del proyecto NPS..."

# Verificar que Docker esté instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instala Docker primero."
    exit 1
fi

# Verificar que Docker Compose esté instalado
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instala Docker Compose primero."
    exit 1
fi

# Detener contenedores existentes si los hay
echo "🛑 Deteniendo contenedores existentes..."
docker-compose down

# Eliminar imágenes antiguas para forzar rebuild
echo "🧹 Limpiando imágenes antiguas..."
docker-compose down --rmi all

# Construir y levantar los contenedores
echo "🔨 Construyendo y levantando contenedores..."
docker-compose up -d --build

# Esperar a que los servicios estén listos
echo "⏳ Esperando a que los servicios estén listos..."
sleep 30

# Verificar el estado de los contenedores
echo "📊 Verificando estado de los contenedores..."
docker-compose ps

# Verificar que los servicios estén respondiendo
echo "🔍 Verificando servicios..."

# Verificar PHP
if curl -f http://localhost:8080 > /dev/null 2>&1; then
    echo "✅ Servicio PHP está funcionando en http://localhost:8080"
else
    echo "❌ Servicio PHP no está respondiendo"
fi

# Verificar phpMyAdmin
if curl -f http://localhost:8081 > /dev/null 2>&1; then
    echo "✅ phpMyAdmin está funcionando en http://localhost:8081"
else
    echo "❌ phpMyAdmin no está respondiendo"
fi

# Verificar MySQL
if docker-compose exec mysql mysqladmin ping -h localhost --silent; then
    echo "✅ MySQL está funcionando"
else
    echo "❌ MySQL no está respondiendo"
fi

echo "🎉 ¡Despliegue completado!"
echo ""
echo "📋 Información de acceso:"
echo "   - Aplicación PHP: http://localhost:8080"
echo "   - phpMyAdmin: http://localhost:8081"
echo "   - MySQL: localhost:3306"
echo ""
echo "🔧 Comandos útiles:"
echo "   - Ver logs: docker-compose logs -f"
echo "   - Detener: docker-compose down"
echo "   - Reiniciar: docker-compose restart" 