<?php
/**
 * Application Configuration
 * Smart Bin Waste Management System
 */

// Application Settings
define('APP_NAME', 'Smart Bin Waste Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/smart-bin');
define('APP_ENV', 'development'); // development, production

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'smart_bin_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 10); // password_hash cost factor

// API Settings
define('API_REQUEST_TIMEOUT', 30);
define('API_RATE_LIMIT_REQUESTS', 100);
define('API_RATE_LIMIT_WINDOW', 3600); // 1 hour

// Notification Settings (loaded from database or hardcoded)
define('NOTIFICATION_YELLOW_THRESHOLD', 50); // %
define('NOTIFICATION_RED_THRESHOLD', 80); // %
define('DEVICE_OFFLINE_TIMEOUT', 300); // seconds

// Sensor Settings
define('SENSOR_EMPTY_DISTANCE', 333); // cm
define('SENSOR_FULL_DISTANCE', 66); // cm

// ESP32 Settings
define('ESP32_POLLING_INTERVAL', 5); // seconds
define('MAX_DISTANCE_VALID', 400); // cm
define('MIN_DISTANCE_VALID', 0); // cm

// Email Configuration (Use Environment Variables in Production)
define('MAIL_DRIVER', 'smtp');
define('MAIL_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', getenv('SMTP_PORT') ?: 587);
define('MAIL_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM') ?: 'smart-bin@example.com');
define('MAIL_FROM_NAME', 'Smart Bin System');

// SMS Configuration (Use Environment Variables in Production)
define('SMS_PROVIDER', getenv('SMS_PROVIDER') ?: 'twilio');
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');
define('SMS_API_SECRET', getenv('SMS_API_SECRET') ?: '');
define('SMS_SENDER', getenv('SMS_SENDER') ?: 'SmartBin');
define('SMS_ENDPOINT', getenv('SMS_ENDPOINT') ?: 'https://api.twilio.com/2010-04-01/Accounts');

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_UPLOAD_TYPES', array('jpg', 'jpeg', 'png', 'pdf', 'txt', 'csv'));
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Logging Settings
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'debug'); // error, warning, info, debug

// Enable error reporting in development
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_DIR . 'error.log');
}

// Date and Time Settings
date_default_timezone_set('UTC');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_TIME_FORMAT', 'h:i A');

// Roles and Permissions
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_EMPLOYEE', 'EMPLOYEE');

// Bin Status Constants
define('BIN_STATUS_NORMAL', 'NORMAL');
define('BIN_STATUS_NEARLY_FULL', 'NEARLY_FULL');
define('BIN_STATUS_FULL', 'FULL');

// Device Status Constants
define('DEVICE_STATUS_ONLINE', 'ONLINE');
define('DEVICE_STATUS_OFFLINE', 'OFFLINE');

// Notification Constants
define('NOTIFICATION_TYPE_NEARLY_FULL', 'NEARLY_FULL');
define('NOTIFICATION_TYPE_FULL', 'FULL');
define('NOTIFICATION_TYPE_SYSTEM', 'SYSTEM');

define('NOTIFICATION_CHANNEL_WEBSITE', 'WEBSITE');
define('NOTIFICATION_CHANNEL_SMS', 'SMS');
define('NOTIFICATION_CHANNEL_EMAIL', 'EMAIL');

define('NOTIFICATION_STATUS_PENDING', 'PENDING');
define('NOTIFICATION_STATUS_SENT', 'SENT');
define('NOTIFICATION_STATUS_FAILED', 'FAILED');
define('NOTIFICATION_STATUS_READ', 'READ');

// Collection Status Constants
define('COLLECTION_STATUS_PENDING', 'PENDING');
define('COLLECTION_STATUS_COMPLETED', 'COMPLETED');
define('COLLECTION_STATUS_CANCELLED', 'CANCELLED');

// Maintenance Status Constants
define('MAINTENANCE_STATUS_PENDING', 'PENDING');
define('MAINTENANCE_STATUS_IN_PROGRESS', 'IN_PROGRESS');
define('MAINTENANCE_STATUS_RESOLVED', 'RESOLVED');
