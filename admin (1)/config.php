<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'rudsstore_panel_bot');
define('DB_USER', 'rudsstore_panel_bot');
define('DB_PASS', '-JLFK,,Vy=9$ihEC');

// Konfigurasi API Provider
define('API_URL', 'http://213.163.206.110:3333/api');
define('API_RESELLER_CODE', 'NF00087');
define('API_PIN', '999105');
define('API_PASSWORD', 'Rudal123');

// Konfigurasi Website
define('SITE_NAME', 'Rud\'s Store');
define('SITE_URL', 'https://rudsstore.my.id');
define('ADMIN_EMAIL', 'admin@rudsstore.my.id');
define('SUPPORT_PHONE', '6287847526737');

// Konfigurasi WhatsApp
define('WHATSAPP_URL', 'https://wa.me/6287847526737');
define('WHATSAPP_GROUP', 'https://chat.whatsapp.com/FijsJY1wzcSJxR3vpaC5q4');

// Mode Debug
define('DEBUG_MODE', true);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set 1 jika menggunakan HTTPS

    session_start();
}
?>