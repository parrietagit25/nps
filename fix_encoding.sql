-- Script para corregir la codificación de caracteres en la base de datos NPS
-- Ejecutar este script en phpMyAdmin o directamente en MySQL

USE nps_db;

-- Actualizar la campaña de ejemplo con caracteres correctos
UPDATE campaigns SET 
    name = 'Encuesta General de Satisfacción',
    description = 'Encuesta para medir la satisfacción general de nuestros clientes',
    question = '¿Qué tan probable es que recomiendes nuestro servicio a un amigo o colega?'
WHERE id = 1;

-- Verificar que los datos se actualizaron correctamente
SELECT id, name, description, question FROM campaigns WHERE id = 1;

-- Verificar la configuración de caracteres de la base de datos
SHOW VARIABLES LIKE 'character_set%';
SHOW VARIABLES LIKE 'collation%';
