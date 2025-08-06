# Script para instalar Composer en Windows
Write-Host "Descargando Composer..." -ForegroundColor Green

# Descargar Composer
Invoke-WebRequest -Uri "https://getcomposer.org/composer-stable.phar" -OutFile "composer.phar"

# Mover a una ubicación en el PATH
if (Test-Path "C:\ProgramData\ComposerSetup\bin") {
    Copy-Item "composer.phar" "C:\ProgramData\ComposerSetup\bin\composer.phar"
    Write-Host "Composer instalado en C:\ProgramData\ComposerSetup\bin\composer.phar" -ForegroundColor Green
} else {
    # Crear directorio si no existe
    New-Item -ItemType Directory -Path "C:\ProgramData\ComposerSetup\bin" -Force
    Copy-Item "composer.phar" "C:\ProgramData\ComposerSetup\bin\composer.phar"
    Write-Host "Composer instalado en C:\ProgramData\ComposerSetup\bin\composer.phar" -ForegroundColor Green
}

# Instalar dependencias del proyecto
Write-Host "Instalando dependencias..." -ForegroundColor Green
php composer.phar install --no-dev --optimize-autoloader --no-interaction --no-scripts

Write-Host "Instalación completada!" -ForegroundColor Green 