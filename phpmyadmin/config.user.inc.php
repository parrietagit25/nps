<?php
/**
 * phpMyAdmin configuration file for NPS Project
 * This file configures authentication and security settings
 */

// Force authentication
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['Servers'][$i]['AllowNoPassword'] = false;

// Security settings
$cfg['LoginCookieValidity'] = 1440; // 24 hours
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;

// Session settings
$cfg['SessionSavePath'] = '/tmp';
$cfg['SessionMaxTime'] = 1440;

// Upload settings
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';
$cfg['MaxSizeForInputField'] = 100 * 1024 * 1024; // 100MB

// Security headers
$cfg['SendErrorReports'] = 'never';
$cfg['ShowServerInfo'] = false;
$cfg['ShowPhpInfo'] = false;
$cfg['ShowChgPassword'] = false;
$cfg['ShowCreateDb'] = false;

// Theme and appearance
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultLang'] = 'en';

// Navigation settings
$cfg['MaxNavigationItems'] = 100;
$cfg['FirstLevelNavigationItems'] = 50;

// Query settings
$cfg['MaxRows'] = 50;
$cfg['Order'] = 'ASC';
$cfg['SaveCellsAtOnce'] = true;

// Export settings
$cfg['Export']['compression'] = 'none';
$cfg['Export']['format'] = 'sql';
$cfg['Export']['charset'] = 'utf-8';

// Import settings
$cfg['Import']['charset'] = 'utf-8';
$cfg['Import']['allow_interrupt'] = true;
$cfg['Import']['skip_queries'] = 0;

// SQL settings
$cfg['SQLQuery']['Edit'] = true;
$cfg['SQLQuery']['Explain'] = true;
$cfg['SQLQuery']['ShowAsPHP'] = true;
$cfg['SQLQuery']['Refresh'] = true;

// Error reporting
$cfg['Error_Handler']['display'] = false;
$cfg['Error_Handler']['gather'] = false;

// Trusted proxies (if behind a reverse proxy)
$cfg['TrustedProxies'] = array();

// Fix for session cookie issues
$cfg['ForceSSL'] = false;
$cfg['LoginCookieValidityDisableWarning'] = true;
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;
$cfg['LoginCookieValidity'] = 1440;

// Allow HTTP access
$cfg['PmaAbsoluteUri'] = 'http://54.94.232.102:8080/';
?> 