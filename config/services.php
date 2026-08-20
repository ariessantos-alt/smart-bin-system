<?php
/**
 * Services Configuration and Initialization
 * Smart Bin Waste Management System
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';

// Service Classes Autoloader
spl_autoload_register(function ($class) {
    $servicesDir = __DIR__ . '/../services/';
    $classPath = $servicesDir . $class . '.php';
    
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

// Initialize Global Services
$notificationService = new NotificationService($db);
$emailService = new EmailService();
$smsService = new SMSService();
$binStatusService = new BinStatusService($db);
$authService = new AuthService($db);

// Helper functions
function logError($message, $context = array()) {
    $logFile = LOG_DIR . 'error.log';
    $timestamp = date(DATE_FORMAT);
    $logMessage = "[$timestamp] ERROR: $message";
    
    if (!empty($context)) {
        $logMessage .= ' | ' . json_encode($context);
    }
    
    error_log($logMessage . PHP_EOL, 3, $logFile);
}

function logInfo($message, $context = array()) {
    if (LOG_LEVEL === 'debug' || LOG_LEVEL === 'info') {
        $logFile = LOG_DIR . 'info.log';
        $timestamp = date(DATE_FORMAT);
        $logMessage = "[$timestamp] INFO: $message";
        
        if (!empty($context)) {
            $logMessage .= ' | ' . json_encode($context);
        }
        
        error_log($logMessage . PHP_EOL, 3, $logFile);
    }
}

function sendJSON($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array(
        'success' => $success,
        'message' => $message
    );
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function validateInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getNextBinCode($db) {
    try {
        $stmt = $db->prepare('SELECT bin_code FROM bins ORDER BY id DESC LIMIT 1');
        $stmt->execute();
        $lastBin = $stmt->fetch();
        
        if ($lastBin) {
            $lastCode = $lastBin['bin_code'];
            $number = (int)substr($lastCode, 3);
            return 'WB-' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);
        }
        
        return 'WB-001';
    } catch (Exception $e) {
        logError('Error getting next bin code', array('error' => $e->getMessage()));
        return 'WB-001';
    }
}

function formatTimeAgo($timestamp) {
    $now = new DateTime();
    $past = new DateTime($timestamp);
    $diff = $now->diff($past);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    
    return 'Just now';
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}
