<?php
require_once __DIR__ . '/../../config/session.php';
SessionManager::startSession();
SessionManager::requireRole('company');
header('Content-Type: application/json');
$currentUser = SessionManager::getCurrentUser();
$companyId = SessionManager::getCurrentCompanyId();
if (!$companyId) {
    echo json_encode(['success' => false, 'message' => 'Firma bilginiz bulunamadı.']);
    exit;
}
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discountValue = intval($_POST['discount_value'] ?? 0);
            $expiryDate = $_POST['expiry_date'] ?? null;
            $usageLimit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
            if (empty($code)) {
                throw new Exception('Kupon kodu zorunludur.');
            }
            if ($discountValue <= 0 || $discountValue > 100) {
                throw new Exception('İndirim yüzdesi 1-100 arasında olmalıdır.');
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
                INSERT INTO Coupons (id, company_id, code, discount, expire_date, usage_limit, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, datetime("now"))
            ');
            $stmt->execute([$couponId, $companyId, $code, $discountValue, $expiryDate, $usageLimit]);
            echo json_encode([
                'success' => true,
                'message' => 'Kupon başarıyla oluşturuldu.'
            ]);
            break;
        case 'update':
            $couponId = $_POST['coupon_id'] ?? '';
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discountValue = intval($_POST['discount_value'] ?? 0);
            $expiryDate = $_POST['expiry_date'] ?? null;
            $usageLimit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
            if (empty($couponId) || empty($code)) {
                throw new Exception('Kupon ID ve kupon kodu zorunludur.');
            }
            if ($discountValue <= 0 || $discountValue > 100) {
                throw new Exception('İndirim yüzdesi 1-100 arasında olmalıdır.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$couponId, $companyId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Bu kupona erişim yetkiniz yok.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Coupons WHERE code = ? AND id != ?');
            $stmt->execute([$code, $couponId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu kupon kodu başka bir kupon tarafından kullanılıyor.');
            }
            $stmt = $db->prepare('
                UPDATE Coupons 
                SET code = ?, discount = ?, expire_date = ?, usage_limit = ?
                WHERE id = ? AND company_id = ?
            ');
            $stmt->execute([$code, $discountValue, $expiryDate, $usageLimit, $couponId, $companyId]);
            echo json_encode([
                'success' => true,
                'message' => 'Kupon başarıyla güncellendi.'
            ]);
            break;
        case 'delete':
            $couponId = $_POST['coupon_id'] ?? '';
            if (empty($couponId)) {
                throw new Exception('Kupon ID gereklidir.');
            }
            $stmt = $db->prepare('SELECT code FROM Coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$couponId, $companyId]);
            $coupon = $stmt->fetch();
            if (!$coupon) {
                throw new Exception('Bu kupona erişim yetkiniz yok.');
            }
            $stmt = $db->prepare('DELETE FROM Coupons WHERE id = ? AND company_id = ?');
            $stmt->execute([$couponId, $companyId]);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Kupon bulunamadı.');
            }
            echo json_encode([
                'success' => true,
                'message' => 'Kupon başarıyla silindi.'
            ]);
            break;
        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (PDOException $e) {
    error_log('Database error in coupon_actions.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in coupon_actions.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>