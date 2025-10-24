<?php
class SessionManager {
    const SESSION_TIMEOUT = 7200;
    const ROLE_USER = 'user';
    const ROLE_COMPANY_ADMIN = 'company';
    const ROLE_ADMIN = 'admin';
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    public static function login($userId, $userRole, $companyId = null, $userData = []) {
        self::startSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['company_id'] = $companyId;
        $_SESSION['login_time'] = time();
        if (!empty($userData)) {
            $_SESSION['user_data'] = $userData;
        }
    }
    public static function logout() {
        self::startSession();
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    public static function getCurrentUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    public static function getCurrentUserRole() {
        self::startSession();
        return $_SESSION['user_role'] ?? null;
    }
    public static function getCurrentCompanyId() {
        self::startSession();
        return isset($_SESSION['company_id']) ? $_SESSION['company_id'] : null;
    }
    public static function getCurrentUserData() {
        self::startSession();
        return $_SESSION['user_data'] ?? [];
    }
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        $userData = self::getCurrentUserData();
        if (empty($userData)) {
            $userId = self::getCurrentUserId();
            if ($userId) {
                require_once __DIR__ . '/../classes/User.php';
                $user = new User();
                $userData = $user->getUserById($userId);
                if ($userData) {
                    $_SESSION['user_data'] = $userData;
                }
            }
        }
        if ($userData && !isset($userData['company_id'])) {
            $userData['company_id'] = $_SESSION['company_id'] ?? null;
        }
        return $userData;
    }
    public static function hasRole($role) {
        return self::getCurrentUserRole() === $role;
    }
    public static function hasAnyRole($roles) {
        $currentRole = self::getCurrentUserRole();
        return in_array($currentRole, $roles);
    }
    public static function isAdmin() {
        return self::hasRole(self::ROLE_ADMIN);
    }
    public static function isCompanyAdmin() {
        return self::hasRole(self::ROLE_COMPANY_ADMIN);
    }
    public static function isUser() {
        return self::hasRole(self::ROLE_USER);
    }
    public static function canAccessCompany($companyId) {
        if (self::isAdmin()) {
            return true;
        }
        if (self::isCompanyAdmin()) {
            return self::getCurrentCompanyId() === $companyId;
        }
        return false;
    }
    public static function requireLogin($redirectUrl = null) {
        if (!self::isLoggedIn()) {
            $loginUrl = (basename(dirname($_SERVER['PHP_SELF'])) == 'pages') ? 'login.php' : 'pages/login.php';
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            }
            header('Location: ' . $loginUrl);
            exit();
        }
    }
    public static function requireRole($role) {
        self::requireLogin();
        if (!self::hasRole($role)) {
            self::setFlashMessage('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'error');
            $indexUrl = (basename(dirname($_SERVER['PHP_SELF'])) == 'pages') ? '../index.php' : 'index.php';
            header('Location: ' . $indexUrl);
            exit();
        }
    }
    public static function requireAnyRole($roles) {
        self::requireLogin();
        if (!self::hasAnyRole($roles)) {
            self::setFlashMessage('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'error');
            $indexUrl = (basename(dirname($_SERVER['PHP_SELF'])) == 'pages') ? '../index.php' : 'index.php';
            header('Location: ' . $indexUrl);
            exit();
        }
    }
    public static function requireAdmin() {
        self::requireRole(self::ROLE_ADMIN);
    }
    public static function requireCompanyAdmin() {
        self::requireRole(self::ROLE_COMPANY_ADMIN);
    }
    public static function setFlashMessage($message, $type = 'info') {
        self::startSession();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    public static function getFlashMessage() {
        self::startSession();
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
    public static function generateCSRFToken() {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function validateCSRFToken($token) {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}