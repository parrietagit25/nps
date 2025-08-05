#!/bin/bash

# NPS Project - Generate Secure Credentials
# Este script genera credenciales seguras para phpMyAdmin

echo "🔐 Generando credenciales seguras para NPS Project..."

# Generar contraseña segura para MySQL
MYSQL_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Generar contraseña para phpMyAdmin (diferente de MySQL)
PMA_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

echo "✅ Credenciales generadas:"
echo ""
echo "📋 MySQL Credentials:"
echo "   Database: nps_db"
echo "   Username: nps_user"
echo "   Password: $MYSQL_PASSWORD"
echo "   Root Password: $MYSQL_ROOT_PASSWORD"
echo ""
echo "🔐 phpMyAdmin Credentials:"
echo "   Username: nps_user"
echo "   Password: $PMA_PASSWORD"
echo ""
echo "🌐 Access URLs:"
echo "   Application: http://54.94.232.102"
echo "   phpMyAdmin: http://54.94.232.102:8080"
echo ""

# Crear archivo .env con las credenciales
cat > .env << EOF
# NPS Project Environment Variables
# Generated on $(date)

# MySQL Configuration
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
MYSQL_DATABASE=nps_db
MYSQL_USER=nps_user
MYSQL_PASSWORD=$MYSQL_PASSWORD

# phpMyAdmin Configuration
PMA_HOST=mysql
PMA_USER=nps_user
PMA_PASSWORD=$PMA_PASSWORD

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://54.94.232.102
EOF

echo "📄 Archivo .env creado con las credenciales"
echo "🔒 Guarda estas credenciales en un lugar seguro!"
echo ""
echo "🚀 Para aplicar los cambios:"
echo "   docker-compose down"
echo "   docker-compose up -d" 