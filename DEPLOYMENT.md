# 🚀 Guía de Despliegue en Servidor

Esta guía te ayudará a desplegar el proyecto NPS en tu servidor.

## 📋 Requisitos del Servidor

### Software Requerido:
- **Docker** (versión 20.10 o superior)
- **Docker Compose** (versión 2.0 o superior)
- **Git** (para clonar el repositorio)

### Recursos Mínimos:
- **CPU:** 2 cores
- **RAM:** 4GB
- **Disco:** 20GB de espacio libre
- **Red:** Conexión a internet para descargar imágenes Docker

## 🛠️ Instalación en el Servidor

### 1. Instalar Docker (Ubuntu/Debian)

```bash
# Actualizar el sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release

# Agregar la clave GPG oficial de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Agregar el repositorio de Docker
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Instalar Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Agregar usuario al grupo docker
sudo usermod -aG docker $USER

# Habilitar Docker al inicio
sudo systemctl enable docker
sudo systemctl start docker
```

### 2. Instalar Docker Compose

```bash
# Descargar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Dar permisos de ejecución
sudo chmod +x /usr/local/bin/docker-compose

# Verificar instalación
docker-compose --version
```

### 3. Clonar el Repositorio

```bash
# Crear directorio para el proyecto
mkdir -p /opt/nps
cd /opt/nps

# Clonar el repositorio
git clone https://github.com/parrietagit25/nps.git .

# Navegar al directorio del proyecto
cd nps2
```

## 🚀 Despliegue

### Opción 1: Despliegue Automático (Recomendado)

```bash
# Dar permisos de ejecución al script
chmod +x deploy.sh

# Ejecutar el script de despliegue
./deploy.sh
```

### Opción 2: Despliegue Manual

```bash
# Construir y levantar los contenedores
docker-compose up -d --build

# Verificar el estado
docker-compose ps

# Ver logs
docker-compose logs -f
```

### Opción 3: Despliegue en Producción

```bash
# Usar configuración de producción
docker-compose -f docker-compose.prod.yml up -d --build
```

## 🔧 Configuración del Servidor

### Configurar Firewall (Ubuntu/Debian)

```bash
# Instalar UFW si no está instalado
sudo apt install ufw

# Configurar reglas básicas
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir SSH
sudo ufw allow ssh

# Permitir puertos de la aplicación
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS (si usas SSL)
sudo ufw allow 8080/tcp  # phpMyAdmin (si lo necesitas)

# Habilitar firewall
sudo ufw enable
```

### Configurar Nginx como Proxy (Opcional)

Si quieres usar un dominio personalizado:

```bash
# Instalar Nginx
sudo apt install nginx

# Crear configuración
sudo nano /etc/nginx/sites-available/nps
```

Contenido del archivo de configuración:
```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Habilitar el sitio
sudo ln -s /etc/nginx/sites-available/nps /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 📊 Monitoreo

### Verificar Estado de los Servicios

```bash
# Ver contenedores en ejecución
docker ps

# Ver logs en tiempo real
docker-compose logs -f

# Ver uso de recursos
docker stats
```

### Comandos Útiles

```bash
# Reiniciar servicios
docker-compose restart

# Detener servicios
docker-compose down

# Ver logs de un servicio específico
docker-compose logs php
docker-compose logs mysql

# Acceder al contenedor PHP
docker-compose exec php bash

# Acceder a MySQL
docker-compose exec mysql mysql -u nps_user -p nps_db
```

## 🔒 Seguridad

### Cambiar Contraseñas por Defecto

```bash
# Editar docker-compose.yml y cambiar las contraseñas
nano docker-compose.yml
```

Cambiar estas líneas:
```yaml
environment:
  - MYSQL_ROOT_PASSWORD=tu_contraseña_segura
  - MYSQL_PASSWORD=tu_contraseña_segura
  - PMA_PASSWORD=tu_contraseña_segura
```

### Configurar SSL (Recomendado para Producción)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com
```

## 🐛 Solución de Problemas

### Si los puertos están ocupados:
```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :3306

# Cambiar puertos en docker-compose.yml
```

### Si hay problemas de permisos:
```bash
# Cambiar propietario de los archivos
sudo chown -R $USER:$USER /opt/nps
```

### Si MySQL no inicia:
```bash
# Ver logs de MySQL
docker-compose logs mysql

# Verificar espacio en disco
df -h
```

### Backup de Base de Datos:
```bash
# Crear backup
docker-compose exec mysql mysqldump -u nps_user -p nps_db > backup.sql

# Restaurar backup
docker-compose exec -T mysql mysql -u nps_user -p nps_db < backup.sql
```

## 📞 Soporte

Si tienes problemas durante el despliegue:

1. Verifica los logs: `docker-compose logs`
2. Asegúrate de que Docker esté funcionando: `docker --version`
3. Verifica que los puertos no estén ocupados
4. Revisa que tengas suficiente espacio en disco

## 🎉 ¡Listo!

Una vez completado el despliegue, tu aplicación estará disponible en:
- **Aplicación:** http://tu-servidor:8080
- **phpMyAdmin:** http://tu-servidor:8081
- **MySQL:** tu-servidor:3306 