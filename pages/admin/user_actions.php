<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../classes/User.php';
SessionManager::startSession();
if (!SessionManager::hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}
header('Content-Type: application/json');
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $action = $_POST['action'] ?? 'create';
    switch ($action) {
        case 'add':
        case 'subtract':
            $userId = $_POST['user_id'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $note = $_POST['note'] ?? '';
            if (empty($userId)) {
                throw new Exception('Kullanıcı ID gereklidir.');
            }
            if ($amount <= 0) {
                throw new Exception('Geçerli bir miktar giriniz.');
            }
            $stmt = $db->prepare('SELECT full_name, balance FROM User WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            $currentBalance = $user['balance'] ?? 0;
            $newBalance = $currentBalance;
            if ($action === 'add') {
                $newBalance = $currentBalance + $amount;
                $message = number_format($amount, 2) . ' ₺ başarıyla eklendi. Yeni bakiye: ' . number_format($newBalance, 2) . ' ₺';
            } else {
                if ($currentBalance < $amount) {
                    throw new Exception('Yetersiz bakiye. Mevcut bakiye: ' . number_format($currentBalance, 2) . ' ₺');
                }
                $newBalance = $currentBalance - $amount;
                $message = number_format($amount, 2) . ' ₺ başarıyla düşüldü. Yeni bakiye: ' . number_format($newBalance, 2) . ' ₺';
            }
            $stmt = $db->prepare('UPDATE User SET balance = ? WHERE id = ?');
            $stmt->execute([$newBalance, $userId]);
            echo json_encode([
                'success' => true,
                'message' => $message,
                'newBalance' => $newBalance
            ]);
            break;
        case 'create':
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';
            $companyId = $_POST['company_id'] ?? null;
            if (empty($fullName) || empty($email) || empty($role) || empty($password)) {
                throw new Exception('Tüm alanlar zorunludur.');
            }
            if (strlen($password) < 8) {
                throw new Exception('Şifre en az 8 karakter olmalıdır.');
            }
            if ($role === 'company' && empty($companyId)) {
                throw new Exception('Firma kullanıcısı için firma seçimi zorunludur.');
            }
            if ($role !== 'company') {
                $companyId = null;
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM User WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu e-posta adresi zaten kullanılıyor.');
            }
            if ($companyId) {
                $stmt = $db->prepare('SELECT COUNT(*) FROM Bus_Company WHERE id = ?');
                $stmt->execute([$companyId]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Seçilen firma bulunamadı.');
                }
            }
            $userId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('
                INSERT INTO User (id, full_name, email, role, password, company_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $fullName,
                $email,
                $role,
                $hashedPassword,
                $companyId,
                date('Y-m-d H:i:s')
            ]);
            echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu.']);
            break;
        case 'edit':
            $userId = $_POST['user_id'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';
            $companyId = $_POST['company_id'] ?? null;
            if (empty($userId) || empty($fullName) || empty($email) || empty($role)) {
                throw new Exception('Gerekli alanlar eksik.');
            }
            if ($role === 'company' && empty($companyId)) {
                throw new Exception('Firma kullanıcısı için firma seçimi zorunludur.');
            }
            if ($role !== 'company') {
                $companyId = null;
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM User WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
            }
            if ($companyId) {
                $stmt = $db->prepare('SELECT COUNT(*) FROM Bus_Company WHERE id = ?');
                $stmt->execute([$companyId]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Seçilen firma bulunamadı.');
                }
            }
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception('Şifre en az 8 karakter olmalıdır.');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('
                    UPDATE User 
                    SET full_name = ?, email = ?, role = ?, password = ?, company_id = ? 
                    WHERE id = ?
                ');
                $stmt->execute([$fullName, $email, $role, $hashedPassword, $companyId, $userId]);
            } else {
                $stmt = $db->prepare('
                    UPDATE User 
                    SET full_name = ?, email = ?, role = ?, company_id = ? 
                    WHERE id = ?
                ');
                $stmt->execute([$fullName, $email, $role, $companyId, $userId]);
            }
            echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi.']);
            break;
        case 'delete':
            $userId = $_POST['user_id'] ?? '';
            if (empty($userId)) {
                throw new Exception('Kullanıcı ID gerekli.');
            }
            $stmt = $db->prepare('SELECT role FROM User WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            if ($user['role'] === 'admin') {
                throw new Exception('Admin kullanıcıları silinemez.');
            }
            $stmt = $db->prepare('DELETE FROM User WHERE id = ?');
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla silindi.']);
            break;
        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>