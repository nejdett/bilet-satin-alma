<?php
require_once __DIR__ . '/../../config/session.php';
SessionManager::startSession();
SessionManager::requireRole('company');
header('Content-Type: application/json');
$currentUser = SessionManager::getCurrentUser();
$companyId = $currentUser['company_id'] ?? null;
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
            $departureCity = trim($_POST['departure_city'] ?? '');
            $destinationCity = trim($_POST['destination_city'] ?? '');
            $departureDate = $_POST['departure_date'] ?? '';
            $departureTime = $_POST['departure_time'] ?? '';
            $arrivalDate = $_POST['arrival_date'] ?? '';
            $arrivalTime = $_POST['arrival_time'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $capacity = intval($_POST['capacity'] ?? 40);
            if (empty($departureCity) || empty($destinationCity) || empty($departureDate) || empty($departureTime) || empty($arrivalDate) || empty($arrivalTime)) {
                throw new Exception('Tüm alanlar zorunludur.');
            }
            if ($price <= 0) {
                throw new Exception('Bilet fiyatı 0\'dan büyük olmalıdır.');
            }
            if ($capacity < 20 || $capacity > 60) {
                throw new Exception('Koltuk sayısı 20-60 arasında olmalıdır.');
            }
            $normalizedDeparture = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $departureCity), 'UTF-8');
            $normalizedDestination = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $destinationCity), 'UTF-8');
            if ($normalizedDeparture === $normalizedDestination) {
                throw new Exception('Kalkış ve varış şehri aynı olamaz.');
            }
            $departureDateTime = $departureDate . ' ' . $departureTime . ':00';
            $arrivalDateTime = $arrivalDate . ' ' . $arrivalTime . ':00';
            if (strtotime($departureDateTime) <= time()) {
                throw new Exception('Kalkış zamanı gelecekte olmalıdır.');
            }
            if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
                throw new Exception('Varış zamanı kalkış zamanından sonra olmalıdır.');
            }
            $tripId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmt = $db->prepare('
                INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$tripId, $companyId, $departureCity, $destinationCity, $departureDateTime, $arrivalDateTime, $price, $capacity]);
            echo json_encode([
                'success' => true,
                'message' => 'Sefer başarıyla oluşturuldu.'
            ]);
            break;
        case 'update':
            $tripId = $_POST['trip_id'] ?? '';
            $departureCity = trim($_POST['departure_city'] ?? '');
            $destinationCity = trim($_POST['destination_city'] ?? '');
            $departureDate = $_POST['departure_date'] ?? '';
            $departureTime = $_POST['departure_time'] ?? '';
            $arrivalDate = $_POST['arrival_date'] ?? '';
            $arrivalTime = $_POST['arrival_time'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $capacity = intval($_POST['capacity'] ?? 40);
            if (empty($tripId) || empty($departureCity) || empty($destinationCity) || empty($departureDate) || empty($departureTime) || empty($arrivalDate) || empty($arrivalTime)) {
                throw new Exception('Tüm alanlar zorunludur.');
            }
            if ($price <= 0) {
                throw new Exception('Bilet fiyatı 0\'dan büyük olmalıdır.');
            }
            if ($capacity < 20 || $capacity > 60) {
                throw new Exception('Koltuk sayısı 20-60 arasında olmalıdır.');
            }
            $normalizedDeparture = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $departureCity), 'UTF-8');
            $normalizedDestination = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $destinationCity), 'UTF-8');
            if ($normalizedDeparture === $normalizedDestination) {
                throw new Exception('Kalkış ve varış şehri aynı olamaz.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Trips WHERE id = ? AND company_id = ?');
            $stmt->execute([$tripId, $companyId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Bu sefere erişim yetkiniz yok.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Tickets WHERE trip_id = ?');
            $stmt->execute([$tripId]);
            $soldTickets = $stmt->fetchColumn();
            if ($soldTickets > $capacity) {
                throw new Exception('Koltuk sayısı satılan bilet sayısından (' . $soldTickets . ') az olamaz.');
            }
            $departureDateTime = $departureDate . ' ' . $departureTime . ':00';
            $arrivalDateTime = $arrivalDate . ' ' . $arrivalTime . ':00';
            $stmt = $db->prepare('SELECT departure_time FROM Trips WHERE id = ?');
            $stmt->execute([$tripId]);
            $currentDepartureTime = $stmt->fetchColumn();
            if ($departureDateTime !== $currentDepartureTime && strtotime($departureDateTime) <= time()) {
                throw new Exception('Kalkış zamanı gelecekte olmalıdır.');
            }
            if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
                throw new Exception('Varış zamanı kalkış zamanından sonra olmalıdır.');
            }
            $stmt = $db->prepare('
                UPDATE Trips 
                SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?
                WHERE id = ? AND company_id = ?
            ');
            $stmt->execute([$departureCity, $destinationCity, $departureDateTime, $arrivalDateTime, $price, $capacity, $tripId, $companyId]);
            echo json_encode([
                'success' => true,
                'message' => 'Sefer başarıyla güncellendi.'
            ]);
            break;
        case 'delete':
            $tripId = $_POST['trip_id'] ?? '';
            if (empty($tripId)) {
                throw new Exception('Sefer ID gereklidir.');
            }
            $stmt = $db->prepare('SELECT COUNT(*) FROM Trips WHERE id = ? AND company_id = ?');
            $stmt->execute([$tripId, $companyId]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Bu sefere erişim yetkiniz yok.');
            }
            $stmt = $db->prepare('DELETE FROM Booked_Seats WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ?)');
            $stmt->execute([$tripId]);
            $stmt = $db->prepare('DELETE FROM Tickets WHERE trip_id = ?');
            $stmt->execute([$tripId]);
            $stmt = $db->prepare('DELETE FROM Trips WHERE id = ? AND company_id = ?');
            $stmt->execute([$tripId, $companyId]);
            if ($stmt->rowCount() == 0) {
                throw new Exception('Sefer bulunamadı.');
            }
            echo json_encode([
                'success' => true,
                'message' => 'Sefer ve ilgili tüm biletler başarıyla silindi.'
            ]);
            break;
        case 'get':
            $tripId = $_POST['trip_id'] ?? '';
            if (empty($tripId)) {
                throw new Exception('Sefer ID gereklidir.');
            }
            $stmt = $db->prepare('
                SELECT t.id, t.company_id, t.departure_city, t.destination_city, 
                       t.departure_time, t.arrival_time, t.price, t.capacity,
                       COUNT(DISTINCT tk.id) as sold_tickets,
                       (COALESCE(t.capacity, 40) - COUNT(DISTINCT tk.id)) as remaining_seats
                FROM Trips t
                LEFT JOIN Tickets tk ON t.id = tk.trip_id
                WHERE t.id = ? AND t.company_id = ?
                GROUP BY t.id, t.company_id, t.departure_city, t.destination_city, 
                         t.departure_time, t.arrival_time, t.price, t.capacity
            ');
            $stmt->execute([$tripId, $companyId]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$trip) {
                throw new Exception('Sefer bulunamadı veya erişim yetkiniz yok.');
            }
            $stmt = $db->prepare('
                SELECT tk.*, u.full_name, u.email, bs.seat_number
                FROM Tickets tk
                JOIN User u ON tk.user_id = u.id
                LEFT JOIN Booked_Seats bs ON tk.id = bs.ticket_id
                WHERE tk.trip_id = ?
                ORDER BY tk.created_at DESC
            ');
            $stmt->execute([$tripId]);
            $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = '
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Sefer Bilgileri</h5>
                                <div class="mb-2">
                                    <strong>Güzergah:</strong> ' . htmlspecialchars($trip['departure_city']) . ' → ' . htmlspecialchars($trip['destination_city']) . '
                                </div>
                                <div class="mb-2">
                                    <strong>Kalkış:</strong> ' . date('d.m.Y H:i', strtotime($trip['departure_time'])) . '
                                </div>
                                <div class="mb-2">
                                    <strong>Varış:</strong> ' . date('d.m.Y H:i', strtotime($trip['arrival_time'])) . '
                                </div>
                                <div class="mb-2">
                                    <strong>Fiyat:</strong> ' . number_format($trip['price'], 2) . ' ₺
                                </div>
                                <div class="mb-2">
                                    <strong>Toplam Koltuk:</strong> ' . $trip['capacity'] . '
                                </div>
                                <div class="mb-2">
                                    <strong>Satılan:</strong> <span class="text-success">' . $trip['sold_tickets'] . '</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Kalan:</strong> <span class="text-info">' . $trip['remaining_seats'] . '</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Yolcu Listesi (' . count($passengers) . ')</h5>';
            if (!empty($passengers)) {
                $html .= '<div class="passenger-list" style="max-height: 300px; overflow-y: auto;">';
                foreach ($passengers as $passenger) {
                    $html .= '
                        <div class="d-flex align-items-center mb-2 p-2 bg-white rounded">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <div class="fw-semibold">' . htmlspecialchars($passenger['full_name'] ?? 'N/A') . '</div>
                                <small class="text-muted">' . htmlspecialchars($passenger['email'] ?? 'N/A') . '</small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-success">' . ($passenger['seat_number'] ?? 'N/A') . '</span>
                            </div>
                        </div>';
                }
                $html .= '</div>';
            } else {
                $html .= '<p class="text-muted text-center">Henüz yolcu yok</p>';
            }
            $html .= '
                            </div>
                        </div>
                    </div>
                </div>';
            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
            break;
        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (PDOException $e) {
    error_log('Database error in trip_actions.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in trip_actions.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>