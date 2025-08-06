<?php
/**
 * phpMyAdmin configuration file for NPS Project
 * Configuración para acceso directo sin autenticación
 */

// Configuración del servidor
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['host'] = 'mysql';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['user'] = 'nps_user';
$cfg['Servers'][$i]['password'] = 'nps_password_secure_2024';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;

// Configuración de seguridad
$cfg['LoginCookieValidity'] = 1440;
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;

// Configuración de subida
$cfg['MaxSizeForInputField'] = 100 * 1024 * 1024; // 100MB

// Headers de seguridad
$cfg['SendErrorReports'] = 'never';
$cfg['ShowServerInfo'] = false;
$cfg['ShowPhpInfo'] = false;

// Tema y apariencia
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultLang'] = 'en';

// Permitir acceso HTTP
$cfg['PmaAbsoluteUri'] = 'http://54.94.232.102:8080/';

// Configuración adicional para acceso directo
$cfg['AllowArbitraryServer'] = true;
$cfg['LoginCookieRecall'] = false;
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;
$cfg['LoginCookieValidity'] = 1440;
$cfg['LoginCookieValidityDisableWarning'] = true;
?> 