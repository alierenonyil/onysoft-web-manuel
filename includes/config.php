<?php
/**
 * Configuration File
 * StarAvcisi E-Commerce System
 */

// Error Reporting (production'da kapatılmalı)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // HTTPS aktif
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'staravcisi.com');
define('DB_NAME', 'wawahousesql');
define('DB_USER', 'wawahousekullanici');
define('DB_PASS', '14531453aO.!');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'https://staravcisi.com'); // Sitenizin URL'i
define('SITE_NAME', 'StarAvcısı E-Ticaret');
define('SITE_EMAIL', 'info@staravcisi.com');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ADMIN_PATH', ROOT_PATH . '/admin');

// Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 10);

// Currency
define('CURRENCY', 'TRY');
define('CURRENCY_SYMBOL', '₺');

// Tax Rate (%)
define('TAX_RATE', 20);

// Email Configuration (SMTP - opsiyonel)
define('MAIL_FROM', 'noreply@staravcisi.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Payment Gateway Settings (daha sonra admin panelden yönetilebilir)
define('IYZICO_API_KEY', '');
define('IYZICO_SECRET_KEY', '');
define('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'); // Production: https://api.iyzipay.com

define('PAYTR_MERCHANT_ID', '');
define('PAYTR_MERCHANT_KEY', '');
define('PAYTR_MERCHANT_SALT', '');

// Admin Configuration
define('ADMIN_SESSION_NAME', 'admin_logged_in');
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 saat

// Customer Configuration
define('CUSTOMER_SESSION_NAME', 'customer_logged_in');

// Site Status
define('MAINTENANCE_MODE', false);

// Autoload Classes (gelecekte kullanılabilir)
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Global Functions
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/security.php';
