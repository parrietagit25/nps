# Configuración de SendGrid para NPS System

## Migración de PHPMailer a SendGrid

El proyecto NPS ha sido migrado de PHPMailer a SendGrid para mejorar el rendimiento y la confiabilidad del envío de emails.

## Archivos Modificados

### Nuevos Archivos:
- `src/config/sendgrid.php` - Servicio de SendGrid
- `src/config/env.php` - Cargador de variables de entorno
- `test_sendgrid.php` - Archivo de prueba

### Archivos Actualizados:
- `composer.json` - Agregada dependencia de SendGrid
- `src/admin/send_campaign.php` - Migrado a SendGrid

## Configuración

### 1. Variables de Entorno

Crea un archivo `.env` en la raíz del proyecto con las siguientes variables:

```env
# SendGrid Configuration
SENDGRID_API_KEY=tu_api_key_de_sendgrid
FROM_EMAIL=notificaciones@grupopcr.com.pa
FROM_NAME=PCR notificaciones
```

### 2. Obtener API Key de SendGrid

1. Ve a [SendGrid.com](https://sendgrid.com)
2. Crea una cuenta o inicia sesión
3. Ve a Settings > API Keys
4. Crea una nueva API Key con permisos de "Mail Send"
5. Copia la API Key y agrégala a tu archivo `.env`

### 3. Verificar Dominio

Para mejor deliverability, verifica tu dominio en SendGrid:
1. Ve a Settings > Sender Authentication
2. Verifica tu dominio siguiendo las instrucciones

## Ventajas de SendGrid vs PHPMailer

### SendGrid:
- ✅ Mejor deliverability
- ✅ Analytics y tracking
- ✅ Manejo automático de bounces
- ✅ Escalabilidad
- ✅ API RESTful moderna
- ✅ Plantillas y personalización avanzada

### PHPMailer:
- ❌ Problemas de deliverability
- ❌ Limitaciones de SMTP
- ❌ Sin analytics
- ❌ Configuración compleja

## Pruebas

Para probar la configuración:

1. Asegúrate de tener configurado el archivo `.env`
2. Accede a `http://tu-dominio/test_sendgrid.php`
3. Verifica que el test sea exitoso

## Uso

El sistema ahora usa automáticamente SendGrid para enviar emails de campañas. No se requieren cambios adicionales en el código de la aplicación.

## Troubleshooting

### Error: "API Key not found"
- Verifica que la API Key esté correctamente configurada en `.env`
- Asegúrate de que la API Key tenga permisos de "Mail Send"

### Error: "From email not verified"
- Verifica tu dominio en SendGrid
- O usa un email verificado como remitente

### Error: "Rate limit exceeded"
- SendGrid tiene límites de envío según tu plan
- Considera actualizar tu plan si necesitas enviar más emails
