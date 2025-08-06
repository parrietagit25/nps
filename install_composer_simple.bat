@echo off
echo Descargando Composer...
powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/composer-stable.phar' -OutFile 'composer.phar'"

echo Instalando dependencias...
php composer.phar install --no-dev --optimize-autoloader --no-interaction --no-scripts

echo Instalacion completada!
pause 