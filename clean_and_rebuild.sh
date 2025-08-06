#!/bin/bash

echo "🧹 Limpiando espacio en disco..."

# Limpiar contenedores no utilizados
docker system prune -f

# Limpiar imágenes no utilizadas
docker image prune -f

# Limpiar volúmenes no utilizados
docker volume prune -f

# Limpiar redes no utilizadas
docker network prune -f

# Limpiar todo (más agresivo)
docker system prune -a -f

echo "📦 Verificando espacio disponible..."
df -h

echo "🔧 Reconstruyendo proyecto..."
cd /home/ubuntu/nps

# Parar contenedores
docker-compose down

# Limpiar imágenes específicas del proyecto
docker rmi $(docker images | grep nps | awk '{print $3}') 2>/dev/null || true

# Reconstruir sin cache
docker-compose build --no-cache

# Levantar contenedores
docker-compose up -d

echo "✅ Reconstrucción completada!"
echo "📊 Estado de los contenedores:"
docker-compose ps 