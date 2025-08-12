<?php
/**
 * Script temporal para generar claves de encriptación seguras
 * 
 * USO: php generate_key.php
 * 
 * ⚠️ IMPORTANTE: Elimina este archivo después de generar la clave
 * ⚠️ NO subas este archivo a producción o GitHub
 */

echo "=== GENERADOR DE CLAVES DE ENCRIPTACIÓN ===\n\n";

// Generar clave de 32 bytes (256 bits)
$key = bin2hex(random_bytes(32));

echo "Clave generada (32 bytes):\n";
echo $key . "\n\n";

echo "Para usar esta clave, agrega esta línea a tu archivo .env:\n";
echo "ENCRYPTION_KEY=" . $key . "\n\n";

echo "⚠️  RECUERDA:\n";
echo "1. Guarda esta clave en un lugar seguro\n";
echo "2. Elimina este archivo después de usarlo\n";
echo "3. Nunca compartas esta clave\n";
echo "4. Usa la misma clave en todos los entornos (desarrollo, staging, producción)\n\n";

echo "=== FIN ===\n";
