<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Trip.php';
require_once __DIR__ . '/../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('company');
$trip = new Trip();
$pageTitle = 'Firma Paneli - Sefer Yönetimi';
$currentUser = SessionManager::getCurrentUser();
$companyId = SessionManager::getCurrentCompanyId();
if (!$companyId) {
    $companyId = $currentUser['company_id'] ?? null;
}
if (!$companyId) {
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/../database/bilet-satis-veritabani.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->query('SELECT id FROM Bus_Company LIMIT 1');
        $firstCompany = $stmt->fetch();
        if ($firstCompany) {
            $userId = SessionManager::getCurrentUserId();
            $updateStmt = $db->prepare('UPDATE User SET company_id = ? WHERE id = ?');
            $updateStmt->execute([$firstCompany['id'], $userId]);
            $_SESSION['company_id'] = $firstCompany['id'];
            $companyId = $firstCompany['id'];
        } else {
            header('Location: login.php?error=no_company');
            exit();
        }
    } catch (Exception $e) {
        header('Location: login.php?error=db_error');
        exit();
    }
}
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/bilet-satis-veritabani.db');
    $companyInfo = null;
    if ($companyId) {
        $stmt = $db->prepare('SELECT * FROM Bus_Company WHERE id = ?');
        $stmt->execute([$companyId]);
        $companyInfo = $stmt->fetch();
    }
    $tripCount = 0;
    $ticketCount = 0;
    $totalRevenue = 0;
    $recentTrips = [];
    $recentTickets = [];
    if ($companyId) {
        $currentDateTime = date('Y-m-d H:i:s');
        $tripStmt = $db->prepare('SELECT COUNT(*) FROM Trips WHERE company_id = ? AND departure_time > ?');
        $tripStmt->execute([$companyId, $currentDateTime]);
        $tripCount = $tripStmt->fetchColumn();
        $ticketStmt = $db->prepare('SELECT COUNT(*) FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE tr.company_id = ?');
        $ticketStmt->execute([$companyId]);
        $ticketCount = $ticketStmt->fetchColumn();
        $revenueStmt = $db->prepare('SELECT SUM(tr.price) FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE tr.company_id = ?');
        $revenueStmt->execute([$companyId]);
        $totalRevenue = $revenueStmt->fetchColumn() ?? 0;
        $stmt = $db->prepare('SELECT * FROM Trips WHERE company_id = ? AND departure_time > ? ORDER BY created_date DESC LIMIT 10');
        $stmt->execute([$companyId, $currentDateTime]);
        $recentTrips = $stmt->fetchAll();
        $stmt = $db->prepare('
            SELECT t.*, tr.departure_city, tr.destination_city, tr.price, u.full_name, u.email 
            FROM Tickets t 
            JOIN Trips tr ON t.trip_id = tr.id 
            JOIN User u ON t.user_id = u.id 
            WHERE tr.company_id = ? 
            ORDER BY t.created_at DESC LIMIT 10
        ');
        $stmt->execute([$companyId]);
        $recentTickets = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $companyInfo = null;
    $tripCount = $ticketCount = $totalRevenue = 0;
    $recentTrips = $recentTickets = [];
}
include __DIR__ . '/../includes/header.php';
?>
<div class="company-panel-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                        <h2 class="text-white mb-0 fw-bold">
                            <i class="fas fa-building me-3"></i>Firma Paneli
                        </h2>
                        <p class="text-white-50 mb-0 mt-2">
                            <?php echo $companyInfo ? htmlspecialchars($companyInfo['name']) : 'Firma Yönetimi'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-bus fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($tripCount); ?></h3>
                        <p class="mb-0">Aktif Sefer</p>
                        <small class="text-white-50">Gelecek tarihli</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #28a745, #20c997);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($ticketCount); ?></h3>
                        <p class="mb-0">Satılan Bilet</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #ffc107, #fd7e14);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-lira-sign fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($totalRevenue, 2); ?> ₺</h3>
                        <p class="mb-0">Toplam Gelir</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-bus me-2"></i>Son Eklenen Seferler
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentTrips)): ?>
                            <?php foreach ($recentTrips as $index => $tripData): ?>
                                <?php if ($index > 0): ?>
                                    <hr class="my-0" style="border-color: rgba(0, 0, 0, 0.1);">
                                <?php endif; ?>
                                <div class="p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-bus text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($tripData['departure_city']); ?> → <?php echo htmlspecialchars($tripData['destination_city']); ?>
                                            </h6>
                                            <p class="mb-1 text-muted small">
                                                <?php echo date('d.m.Y H:i', strtotime($tripData['departure_time'])); ?>
                                            </p>
                                            <span class="badge bg-success"><?php echo number_format($tripData['price'], 2); ?> ₺</span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-primary"><?php echo $tripData['capacity'] ?? 40; ?> koltuk</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-bus fa-3x mb-3"></i>
                                <p class="mb-0">Aktif sefer bulunmuyor</p>
                                <small class="text-muted">Gelecek tarihli seferler burada görünecektir</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-ticket-alt me-2"></i>Son Satılan Biletler
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentTickets)): ?>
                            <?php foreach ($recentTickets as $index => $ticket): ?>
                                <?php if ($index > 0): ?>
                                    <hr class="my-0" style="border-color: rgba(0, 0, 0, 0.1);">
                                <?php endif; ?>
                                <div class="p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(45deg, #28a745, #20c997); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-ticket-alt text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($ticket['departure_city']); ?> → <?php echo htmlspecialchars($ticket['destination_city']); ?>
                                            </h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($ticket['full_name']); ?></p>
                                            <span class="badge bg-success"><?php echo number_format($ticket['price'], 2); ?> ₺</span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($ticket['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                                <p class="mb-0">Henüz bilet satılmamış</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #6f42c1, #e83e8c); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-tools me-2"></i>Hızlı İşlemler
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-lg-6 col-md-6">
                                <button onclick="window.location.href='company/trips.php'" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #28a745, #20c997); border: none; border-radius: 15px; color: white; cursor: pointer; min-height: 100px;">
                                    <i class="fas fa-bus fa-2x mb-2"></i>
                                    <span class="fw-semibold">Sefer Yönetimi</span>
                                </button>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <a href="company/coupons.php" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #dc3545, #fd7e14); border: none; border-radius: 15px; color: white; text-decoration: none; min-height: 100px;">
                                    <i class="fas fa-tags fa-2x mb-2"></i>
                                    <span class="fw-semibold">Kupon Oluştur</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="text-white py-4" style="background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-2">
                    </i>Otobüs Bileti Platformu
                </h5>
                <p class="text-white-50 mb-0">Türkiye'nin her yerine güvenli ve konforlu yolculuk.</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="text-white-50 mb-1">&copy; <?php echo date('Y'); ?> Tüm hakları saklıdır.</p>
                <small class="text-white-50">
                    <i class="fas fa-shield-alt me-1"></i>
                    Güvenli ödeme sistemi ile korumalı alışveriş
                </small>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>