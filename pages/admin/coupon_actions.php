<?php
require_once __DIR__ . '/../../config/session.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
header('Content-Type: application/json');
try {
    $dbPath = __DIR__ . '/../../bilet-satis-veritabani.db';
    if (!file_exists($dbPath)) {
        throw new Exception('Veritabanı dosyası bulunamadı: ' . $dbPath);
    }
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('
        CREATE TABLE IF NOT EXISTS Coupons (
            id TEXT PRIMARY KEY,
            code TEXT NOT NULL,
            discount REAL NOT NULL,
            company_id TEXT,
            usage_limit INTEGER NOT NULL,
            expire_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
        )
    ');
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $companyId = !empty($_POST['company_id']) ? $_POST['company_id'] : null;
            $discount = floatval($_POST['discount'] ?? 0);
            $usageLimit = intval($_POST['usage_limit'] ?? 0);
            $expireDate = $_POST['expire_date'] ?? '';
            if (empty($code) || $discount <= 0 || $discount > 100 || $usageLimit <= 0 || empty($expireDate)) {
                throw new Exception('Tüm gerekli alanlar doldurulmalıdır. İndirim oranı 0-100 arasında olmalıdır.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Coupons WHERE code = ?');
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu kupon kodu zaten kullanılıyor.');
            }
            $couponId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmt = $db->prepare('
                INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $result = $stmt->execute([$couponId, $code, $discount, $companyId, $usageLimit, $expireDate]);
            if (!$result) {
                throw new Exception('Veritabanına kayıt eklenemedi: ' . implode(', ', $stmt->errorInfo()));
            }
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM Coupons WHERE id = ?');
            $checkStmt->execute([$couponId]);
            $recordExists = $checkStmt->fetchColumn();
            if ($recordExists == 0) {
                throw new Exception('Kupon oluşturuldu ama veritabanında bulunamadı!');
            }
            echo json_encode([
                'success' => true, 
                'message' => 'İndirim kuponu başarıyla oluşturuldu.'
            ]);
            break;
        case 'update':
            $couponId = $_POST['coupon_id'] ?? '';
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $companyId = !empty($_POST['company_id']) ? $_POST['company_id'] : null;
            $discount = floatval($_POST['discount'] ?? 0);
            $usageLimit = intval($_POST['usage_limit'] ?? 0);
            $expireDate = $_POST['expire_date'] ?? '';
            if (empty($couponId) || empty($code) || $discount <= 0 || $discount > 100 || $usageLimit <= 0 || empty($expireDate)) {
                throw new Exception('Tüm gerekli alanlar doldurulmalıdır. İndirim oranı 0-100 arasında olmalıdır.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Coupons WHERE code = ? AND id != ?');
            $stmt->execute([$code, $couponId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu kupon kodu başka bir kupon tarafından kullanılıyor.');
            }
            $stmt = $db->prepare('
                UPDATE Coupons 
                SET code = ?, discount = ?, company_id = ?, usage_limit = ?, expire_date = ?
                WHERE id = ?
            ');
            $stmt->execute([$code, $discount, $companyId, $usageLimit, $expireDate, $couponId]);
            echo json_encode(['success' => true, 'message' => 'İndirim kuponu başarıyla güncellendi.']);
            break;
        case 'delete':
            $couponId = $_POST['coupon_id'] ?? '';
            if (empty($couponId)) {
                throw new Exception('Kupon ID gereklidir.');
            }
            $stmt = $db->prepare('DELETE FROM Coupons WHERE id = ?');
            $stmt->execute([$couponId]);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Kupon bulunamadı.');
            }
            echo json_encode(['success' => true, 'message' => 'İndirim kuponu başarıyla silindi.']);
            break;
        case 'get':
            $couponId = $_POST['coupon_id'] ?? '';
            if (empty($couponId)) {
                throw new Exception('Kupon ID gereklidir.');
            }
            $stmt = $db->prepare('SELECT * FROM Coupons WHERE id = ?');
            $stmt->execute([$couponId]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$coupon) {
                throw new Exception('Kupon bulunamadı.');
            }
            echo json_encode(['success' => true, 'coupon' => $coupon]);
            break;
        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>