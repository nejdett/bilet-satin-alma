<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/validation.php';
class User {
    private $db;
    const MIN_PASSWORD_LENGTH = 8;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900;
    const ROLE_USER = 'user';
    const ROLE_COMPANY_ADMIN = 'company';
    const ROLE_ADMIN = 'admin';
    public function __construct() {
        $this->db = Database::getInstance();
    }
    // User registration with validation
    public function register($fullname, $email, $password, $role = self::ROLE_USER, $companyId = null) {
        try {
            // Validate input data
            $validation = $this->validateRegistrationData($fullname, $email, $password, $role);
            if (!$validation['success']) {
                return $validation;
            }
            $passwordValidation = $this->validatePasswordStrength($password);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $passwordValidation['errors'])
                ];
            }
            if ($this->emailExists($email)) {
                return [
                    'success' => false,
                    'message' => 'Bu e-posta adresi zaten kullanılmaktadır.'
                ];
            }
            $hashedPassword = $this->hashPassword($password);
            $userId = $this->generateUUID();
            $sql = "INSERT INTO User (id, full_name, email, password, role, company_id, balance, created_at, password_changed_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 800, datetime('now'), datetime('now'))";
            $params = [$userId, $fullname, $email, $hashedPassword, $role, $companyId];
            $this->db->execute($sql, $params);
            SecurityConfig::logSecurityEvent('user_registered', [
                'user_id' => $userId,
                'email' => $email,
                'role' => $role
            ]);
            return [
                'success' => true,
                'message' => 'Kayıt işlemi başarıyla tamamlandı.',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            error_log('User registration error: ' . $e->getMessage());
            SecurityConfig::logSecurityEvent('registration_error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Kayıt işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin.'
            ];
        }
    }    
    public function login($email, $password) {
        try {
            if (!SecurityConfig::checkRateLimit('login', 10)) {
                SecurityConfig::logSecurityEvent('login_rate_limit_exceeded', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'Çok fazla giriş denemesi. Lütfen bir dakika sonra tekrar deneyin.'
                ];
            }
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'E-posta ve şifre alanları zorunludur.'
                ];
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Geçerli bir e-posta adresi giriniz.'
                ];
            }
            if ($this->isAccountLocked($email)) {
                SecurityConfig::logSecurityEvent('login_attempt_on_locked_account', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'Hesabınız geçici olarak kilitlenmiştir. Lütfen daha sonra tekrar deneyin.'
                ];
            }
            $user = $this->getUserByEmail($email);
            if (!$user) {
                $this->recordFailedLogin($email);
                SecurityConfig::logSecurityEvent('login_failed_user_not_found', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'E-posta veya şifre hatalı.'
                ];
            }
            if (!$this->verifyPassword($password, $user['password'])) {
                $this->recordFailedLogin($email);
                SecurityConfig::logSecurityEvent('login_failed_wrong_password', [
                    'email' => $email,
                    'user_id' => $user['id']
                ]);
                return [
                    'success' => false,
                    'message' => 'E-posta veya şifre hatalı.'
                ];
            }
            if ($this->needsRehash($user['password'])) {
                $this->updatePasswordHash($user['id'], $password);
            }
            $this->clearFailedLogins($email);
            $this->updateLastLogin($user['id']);
            SessionManager::login(
                $user['id'], 
                $user['role'], 
                $user['company_id'],
                [
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'balance' => $user['balance']
                ]
            );
            SecurityConfig::logSecurityEvent('login_successful', [
                'user_id' => $user['id'],
                'email' => $email,
                'role' => $user['role']
            ]);
            return [
                'success' => true,
                'message' => 'Giriş başarılı.',
                'user' => [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'company_id' => $user['company_id'],
                    'balance' => $user['balance']
                ]
            ];
        } catch (Exception $e) {
            error_log('User login error: ' . $e->getMessage());
            SecurityConfig::logSecurityEvent('login_error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Giriş işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin.'
            ];
        }
    }
    public function getUserById($userId) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT id, full_name as fullname, email, password, role, company_id, balance 
                    FROM User WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Get user by ID error: ' . $e->getMessage());
            return false;
        }
    }    
    public function getUserByEmail($email) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT id, full_name as fullname, email, password, role, company_id, balance 
                    FROM User WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Get user by email error: ' . $e->getMessage());
            return false;
        }
    }
    public function updateBalance($userId, $amount) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı.'
                ];
            }
            $newBalance = $user['balance'] + $amount;
            if ($newBalance < 0) {
                return [
                    'success' => false,
                    'message' => 'Yetersiz bakiye.'
                ];
            }
            $sql = "UPDATE User SET balance = ? WHERE id = ?";
            $this->db->execute($sql, [$newBalance, $userId]);
            if (SessionManager::getCurrentUserId() === $userId) {
                $userData = SessionManager::getCurrentUserData();
                $userData['balance'] = $newBalance;
                SessionManager::login(
                    $userId,
                    SessionManager::getCurrentUserRole(),
                    SessionManager::getCurrentCompanyId(),
                    $userData
                );
            }
            return [
                'success' => true,
                'message' => 'Bakiye güncellendi.',
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            error_log('Update balance error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bakiye güncellenirken bir hata oluştu.'
            ];
        }
    }   
    public function hasPermission($userId, $action, $resourceId = null) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return false;
            }
            switch ($action) {
                case 'admin_access':
                    return $user['role'] === self::ROLE_ADMIN;
                case 'company_admin_access':
                    return in_array($user['role'], [self::ROLE_ADMIN, self::ROLE_COMPANY_ADMIN]);
                case 'company_data_access':
                    if ($user['role'] === self::ROLE_ADMIN) {
                        return true;
                    }
                    if ($user['role'] === self::ROLE_COMPANY_ADMIN) {
                        return $user['company_id'] === $resourceId;
                    }
                    return false;
                case 'user_data_access':
                    return $user['role'] === self::ROLE_ADMIN || $userId === $resourceId;
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log('Permission check error: ' . $e->getMessage());
            return false;
        }
    }
    private function validateRegistrationData($fullname, $email, $password, $role) {
        $validator = new InputValidator([
            'fullname' => $fullname,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ]);
        $validator->validate('fullname', ['required', 'min_length' => 2, 'max_length' => 100, 'alpha'], 'Ad Soyad')
                  ->validate('email', ['required', 'email', 'max_length' => 255], 'E-posta')
                  ->validate('password', ['required', 'min_length' => self::MIN_PASSWORD_LENGTH], 'Şifre')
                  ->validate('role', ['required'], 'Rol');
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $firstError = reset($errors);
            return [
                'success' => false,
                'message' => is_array($firstError) ? $firstError[0] : $firstError
            ];
        }
        if (!in_array($role, [self::ROLE_USER, self::ROLE_COMPANY_ADMIN, self::ROLE_ADMIN])) {
            return [
                'success' => false,
                'message' => 'Geçersiz kullanıcı rolü.'
            ];
        }
        return ['success' => true];
    }  
    private function emailExists($email) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT COUNT(*) as count FROM User WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log('Email exists check error: ' . $e->getMessage());
            return true;
        }
    }
    public function getBalance($userId) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT balance FROM User WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['balance'] : false;
        } catch (Exception $e) {
            error_log('Get balance error: ' . $e->getMessage());
            return false;
        }
    }
    public function getAllUsersWithCompany() {
        try {
            $sql = "SELECT u.id, u.full_name as fullname, u.email, u.role, u.company_id, u.balance,
                           c.name as company_name
                    FROM User u
                    LEFT JOIN Bus_Company c ON u.company_id = c.id
                    ORDER BY u.role DESC, u.full_name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Get all users with company error: ' . $e->getMessage());
            return [];
        }
    }
    public function addBalance($userId, $amount) {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Eklenecek tutar pozitif olmalıdır.'
            ];
        }
        return $this->updateBalance($userId, $amount);
    }
    public function subtractBalance($userId, $amount) {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Düşülecek tutar pozitif olmalıdır.'
            ];
        }
        return $this->updateBalance($userId, -$amount);
    }
    private function hashPassword($password) {
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = [];
        if ($algorithm === PASSWORD_ARGON2ID) {
            $options = [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ];
        }
        return password_hash($password, $algorithm, $options);
    }
    private function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    private function needsRehash($hash) {
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_needs_rehash($hash, $algorithm);
    }
    private function updatePasswordHash($userId, $password) {
        try {
            $newHash = $this->hashPassword($password);
            $sql = "UPDATE User SET password = ?, password_changed_at = datetime('now') WHERE id = ?";
            $this->db->execute($sql, [$newHash, $userId]);
            return true;
        } catch (Exception $e) {
            error_log('Password rehash error: ' . $e->getMessage());
            return false;
        }
    }
    private function validatePasswordStrength($password) {
        return SecurityConfig::validatePasswordStrength($password);
    }
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı.'
                ];
            }
            if (!$this->verifyPassword($currentPassword, $user['password'])) {
                SecurityConfig::logSecurityEvent('password_change_failed_wrong_current', [
                    'user_id' => $userId
                ]);
                return [
                    'success' => false,
                    'message' => 'Mevcut şifre hatalı.'
                ];
            }
            $passwordValidation = $this->validatePasswordStrength($newPassword);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $passwordValidation['errors'])
                ];
            }
            if ($this->verifyPassword($newPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Yeni şifre mevcut şifreden farklı olmalıdır.'
                ];
            }
            $newHash = $this->hashPassword($newPassword);
            $sql = "UPDATE User SET password = ?, password_changed_at = datetime('now') WHERE id = ?";
            $this->db->execute($sql, [$newHash, $userId]);
            SecurityConfig::logSecurityEvent('password_changed', [
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            return [
                'success' => true,
                'message' => 'Şifre başarıyla değiştirildi.'
            ];
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Şifre değiştirme sırasında bir hata oluştu.'
            ];
        }
    }
    private function recordFailedLogin($email) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sql = "INSERT OR REPLACE INTO failed_logins (email, ip_address, attempts, last_attempt) 
                    VALUES (?, ?, 
                        COALESCE((SELECT attempts FROM failed_logins WHERE email = ? AND ip_address = ?), 0) + 1,
                        datetime('now'))";
            $this->db->execute($sql, [$email, $ip, $email, $ip]);
        } catch (Exception $e) {
            error_log('Record failed login error: ' . $e->getMessage());
        }
    }
    private function isAccountLocked($email) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sql = "SELECT attempts, last_attempt FROM failed_logins 
                    WHERE email = ? AND ip_address = ?";
            $result = $this->db->fetch($sql, [$email, $ip]);
            if (!$result) {
                return false;
            }
            $lastAttempt = strtotime($result['last_attempt']);
            $now = time();
            if ($now - $lastAttempt > self::LOCKOUT_DURATION) {
                $this->clearFailedLogins($email);
                return false;
            }
            return $result['attempts'] >= self::MAX_LOGIN_ATTEMPTS;
        } catch (Exception $e) {
            error_log('Account lock check error: ' . $e->getMessage());
            return false;
        }
    }
    private function clearFailedLogins($email) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sql = "DELETE FROM failed_logins WHERE email = ? AND ip_address = ?";
            $this->db->execute($sql, [$email, $ip]);
        } catch (Exception $e) {
            error_log('Clear failed logins error: ' . $e->getMessage());
        }
    }
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE User SET last_login = datetime('now') WHERE id = ?";
            $this->db->execute($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Update last login error: ' . $e->getMessage());
        }
    }
    public function generatePasswordResetToken($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return [
                    'success' => true,
                    'message' => 'Eğer bu e-posta adresi sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderilecektir.'
                ];
            }
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            $sql = "INSERT OR REPLACE INTO password_reset_tokens (user_id, token, expires_at, created_at) 
                    VALUES (?, ?, ?, datetime('now'))";
            $this->db->execute($sql, [$user['id'], $token, $expiry]);
            SecurityConfig::logSecurityEvent('password_reset_requested', [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            return [
                'success' => true,
                'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
                'token' => $token // In real app, this would be sent via email
            ];
        } catch (Exception $e) {
            error_log('Password reset token generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Şifre sıfırlama işlemi sırasında bir hata oluştu.'
            ];
        }
    }
    public function resetPasswordWithToken($token, $newPassword) {
        try {
            $sql = "SELECT user_id, expires_at FROM password_reset_tokens 
                    WHERE token = ? AND expires_at > datetime('now')";
            $tokenData = $this->db->fetch($sql, [$token]);
            if (!$tokenData) {
                SecurityConfig::logSecurityEvent('password_reset_invalid_token', [
                    'token' => substr($token, 0, 8) . '...'
                ]);
                return [
                    'success' => false,
                    'message' => 'Geçersiz veya süresi dolmuş token.'
                ];
            }
            $passwordValidation = $this->validatePasswordStrength($newPassword);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(' ', $passwordValidation['errors'])
                ];
            }
            $newHash = $this->hashPassword($newPassword);
            $sql = "UPDATE User SET password = ?, password_changed_at = datetime('now') WHERE id = ?";
            $this->db->execute($sql, [$newHash, $tokenData['user_id']]);
            $sql = "DELETE FROM password_reset_tokens WHERE token = ?";
            $this->db->execute($sql, [$token]);
            $user = $this->getUserById($tokenData['user_id']);
            if ($user) {
                $this->clearFailedLogins($user['email']);
            }
            SecurityConfig::logSecurityEvent('password_reset_completed', [
                'user_id' => $tokenData['user_id']
            ]);
            return [
                'success' => true,
                'message' => 'Şifre başarıyla sıfırlandı.'
            ];
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Şifre sıfırlama sırasında bir hata oluştu.'
            ];
        }
    }
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}