<?php
class SecurityConfig {
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_REQUIRE_UPPERCASE = true;
    const PASSWORD_REQUIRE_LOWERCASE = true;
    const PASSWORD_REQUIRE_NUMBERS = true;
    const PASSWORD_REQUIRE_SPECIAL = false;
    const SESSION_TIMEOUT = 1800;
    const SESSION_REGENERATE_INTERVAL = 300;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_LOCKOUT_TIME = 900;
    const MAX_FILE_SIZE = 5242880;
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const ALLOWED_DOCUMENT_TYPES = ['application/pdf', 'text/plain'];
    const MAX_INPUT_LENGTH = 1000;
    const MAX_TEXT_LENGTH = 5000;
    const ALLOWED_HTML_TAGS = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li'];
    const MAX_REQUESTS_PER_MINUTE = 60;
    const MAX_FORM_SUBMISSIONS_PER_MINUTE = 10;
    const CSP_POLICY = [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'style-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'img-src' => "'self' data: https:",
        'font-src' => "'self' https://cdn.jsdelivr.net",
        'connect-src' => "'self'",
        'frame-ancestors' => "'none'",
        'base-uri' => "'self'",
        'form-action' => "'self'"
    ];
    public static function applySecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        $csp = '';
        foreach (self::CSP_POLICY as $directive => $value) {
            $csp .= $directive . ' ' . $value . '; ';
        }
        header('Content-Security-Policy: ' . trim($csp));
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        if (self::isSensitivePage()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    private static function isSensitivePage() {
        $sensitivePaths = [
            '/pages/login.php',
            '/pages/profile.php',
            '/pages/admin/',
            '/pages/company/',
            '/pages/booking.php'
        ];
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($sensitivePaths as $path) {
            if (strpos($currentPath, $path) !== false) {
                return true;
            }
        }
        return false;
    }
    public static function initialize() {
        self::applySecurityHeaders();
        self::configureSession();
        if (self::isProduction()) {
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        }
    }
    private static function configureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);
            ini_set('session.cookie_lifetime', 0); // Session cookies only
            if (isset($_SESSION['last_regeneration'])) {
                if (time() - $_SESSION['last_regeneration'] > self::SESSION_REGENERATE_INTERVAL) {
                    session_regenerate_id(true);
                    $_SESSION['last_regeneration'] = time();
                }
            } else {
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    private static function isProduction() {
        return !in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
    }
    public static function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => SessionManager::getCurrentUserId() ?? 'anonymous',
            'details' => $details
        ];
        $logMessage = '[SECURITY] ' . json_encode($logData);
        error_log($logMessage);
        if (self::isCriticalEvent($event)) {
            self::sendSecurityAlert($event, $logData);
        }
    }
    private static function isCriticalEvent($event) {
        $criticalEvents = [
            'multiple_failed_logins',
            'sql_injection_attempt',
            'xss_attempt',
            'csrf_token_mismatch',
            'unauthorized_access_attempt',
            'file_upload_attack'
        ];
        return in_array($event, $criticalEvents);
    }
    private static function sendSecurityAlert($event, $data) {
        error_log('[CRITICAL SECURITY EVENT] ' . $event . ' - ' . json_encode($data));
    }
    public static function checkRateLimit($action = 'general', $limit = null) {
        $limit = $limit ?: self::MAX_REQUESTS_PER_MINUTE;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . $action . '_' . $ip;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $now = time();
        $window = 60;
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        $data = $_SESSION[$key];
        if ($now - $data['start'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        if ($data['count'] >= $limit) {
            self::logSecurityEvent('rate_limit_exceeded', [
                'action' => $action,
                'limit' => $limit,
                'count' => $data['count']
            ]);
            return false;
        }
        $_SESSION[$key]['count']++;
        return true;
    }
    public static function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Şifre en az ' . self::PASSWORD_MIN_LENGTH . ' karakter olmalıdır.';
        }
        if (self::PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Şifre en az bir büyük harf içermelidir.';
        }
        if (self::PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Şifre en az bir küçük harf içermelidir.';
        }
        if (self::PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Şifre en az bir rakam içermelidir.';
        }
        if (self::PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Şifre en az bir özel karakter içermelidir.';
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}