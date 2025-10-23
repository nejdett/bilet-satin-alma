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
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Firma adı gereklidir.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Bus_Company WHERE name = ?');
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu firma adı zaten kullanılıyor.');
            }
            $companyId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmt = $db->prepare('
                INSERT INTO Bus_Company (id, name, created_at) 
                VALUES (?, ?, datetime("now"))
            ');
            $stmt->execute([$companyId, $name]);
            echo json_encode([
                'success' => true,
                'message' => 'Firma başarıyla oluşturuldu.',
                'company' => [
                    'id' => $companyId,
                    'name' => $name
                ]
            ]);
            break;
        case 'update':
            $companyId = $_POST['company_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            if (empty($companyId) || empty($name)) {
                throw new Exception('Firma ID ve adı gereklidir.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Bus_Company WHERE id = ?');
            $stmt->execute([$companyId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Firma bulunamadı.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Bus_Company WHERE name = ? AND id != ?');
            $stmt->execute([$name, $companyId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu firma adı zaten kullanılıyor.');
            }
            $stmt = $db->prepare('UPDATE Bus_Company SET name = ? WHERE id = ?');
            $stmt->execute([$name, $companyId]);
            echo json_encode([
                'success' => true,
                'message' => 'Firma başarıyla güncellendi.'
            ]);
            break;
        case 'delete':
            $companyId = $_POST['company_id'] ?? '';
            if (empty($companyId)) {
                throw new Exception('Firma ID gereklidir.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Trips WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $tripCount = $stmt->fetchColumn();
            $stmt = $db->prepare('SELECT COUNT(*) FROM User WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $userCount = $stmt->fetchColumn();
            if ($tripCount > 0 || $userCount > 0) {
                throw new Exception('Bu firmaya ait seferler veya kullanıcılar bulunduğu için silinemez.');
            }
            $stmt = $db->prepare('DELETE FROM Bus_Company WHERE id = ?');
            $stmt->execute([$companyId]);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Firma bulunamadı.');
            }
            echo json_encode([
                'success' => true,
                'message' => 'Firma başarıyla silindi.'
            ]);
            break;
        case 'get':
            $companyId = $_POST['company_id'] ?? '';
            if (empty($companyId)) {
                throw new Exception('Firma ID gereklidir.');
            }
            $stmt = $db->prepare('
                SELECT bc.*, 
                       COUNT(DISTINCT t.id) as trip_count,
                       COUNT(DISTINCT u.id) as user_count
                FROM Bus_Company bc
                LEFT JOIN Trips t ON bc.id = t.company_id
                LEFT JOIN User u ON bc.id = u.company_id
                WHERE bc.id = ?
                GROUP BY bc.id
            ');
            $stmt->execute([$companyId]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$company) {
                throw new Exception('Firma bulunamadı.');
            }
            echo json_encode([
                'success' => true,
                'company' => $company
            ]);
            break;
        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile()),
            'action' => $_POST['action'] ?? 'unknown',
            'db_path' => isset($dbPath) ? $dbPath : 'not_set',
            'db_exists' => isset($dbPath) ? file_exists($dbPath) : false
        ]
    ]);
}
?>