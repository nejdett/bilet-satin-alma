<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$tripId = $_GET['id'] ?? null;
if (!$tripId) {
    header('Location: ' . getAbsoluteUrl('pages/admin/trips.php'));
    exit;
}
$pageTitle = 'Sefer Detayları - Admin Paneli';
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $trip = $db->prepare('
        SELECT t.*, bc.name as company_name,
               COUNT(tk.id) as sold_tickets,
               (t.capacity - COUNT(tk.id)) as remaining_seats
        FROM Trips t
        LEFT JOIN Bus_Company bc ON t.company_id = bc.id
        LEFT JOIN Tickets tk ON t.id = tk.trip_id
        WHERE t.id = ?
        GROUP BY t.id
    ');
    $trip->execute([$tripId]);
    $tripData = $trip->fetch();
    if (!$tripData) {
        header('Location: ' . getAbsoluteUrl('pages/admin/trips.php'));
        exit;
    }
    $tickets = $db->prepare('
        SELECT tk.*, u.full_name, u.email, bs.seat_number
        FROM Tickets tk
        JOIN User u ON tk.user_id = u.id
        LEFT JOIN Booked_Seats bs ON tk.id = bs.ticket_id
        WHERE tk.trip_id = ?
        ORDER BY tk.created_at DESC
    ');
    $tickets->execute([$tripId]);
    $ticketList = $tickets->fetchAll();
} catch (Exception $e) {
    $tripData = null;
    $ticketList = [];
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="admin-panel-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="<?php echo getAbsoluteUrl('pages/admin_panel.php'); ?>" class="text-white text-decoration-none">
                        <i class="fas fa-tachometer-alt me-1"></i>Admin Panel
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo getAbsoluteUrl('pages/admin/trips.php'); ?>" class="text-white text-decoration-none">
                        <i class="fas fa-route me-1"></i>Seferler
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50">Sefer Detayları</li>
            </ol>
        </nav>
        <?php if ($tripData): ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="card-header py-4" style="background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 20px 20px 0 0;">
                            <h4 class="text-white mb-0">
                                <i class="fas fa-route me-2"></i>Sefer Detayları
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Kalkış</h6>
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($tripData['departure_city']); ?></h5>
                                    <p class="text-muted"><?php echo date('d.m.Y H:i', strtotime($tripData['departure_time'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Varış</h6>
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($tripData['destination_city']); ?></h5>
                                    <p class="text-muted"><?php echo date('d.m.Y H:i', strtotime($tripData['arrival_time'])); ?></p>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <h6 class="text-muted">Firma</h6>
                                    <p class="fw-bold"><?php echo htmlspecialchars($tripData['company_name']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted">Fiyat</h6>
                                    <p class="fw-bold text-success"><?php echo number_format($tripData['price'], 2); ?> TL</p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted">Kapasite</h6>
                                    <p class="fw-bold"><?php echo $tripData['capacity']; ?> koltuk</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-primary mb-0"><?php echo $tripData['sold_tickets']; ?></h4>
                                        <small class="text-muted">Satılan Bilet</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-success mb-0"><?php echo $tripData['remaining_seats']; ?></h4>
                                        <small class="text-muted">Kalan Koltuk</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-info mb-0">%<?php echo round(($tripData['sold_tickets'] / $tripData['capacity']) * 100, 1); ?></h4>
                                        <small class="text-muted">Doluluk Oranı</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="card-header py-3" style="background: linear-gradient(45deg, #28a745, #1e7e34); border-radius: 20px 20px 0 0;">
                            <h5 class="text-white mb-0">
                                <i class="fas fa-ticket-alt me-2"></i>Satılan Biletler
                            </h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($ticketList)): ?>
                                <?php foreach ($ticketList as $ticket): ?>
                                    <div class="border-bottom p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($ticket['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($ticket['email']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?php echo $ticket['seat_number']; ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo date('d.m H:i', strtotime($ticket['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Henüz bilet satılmamış</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                            <h4 class="fw-bold mb-3">Sefer Bulunamadı</h4>
                            <p class="text-muted">Aradığınız sefer mevcut değil.</p>
                            <a href="<?php echo getAbsoluteUrl('pages/admin/trips.php'); ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Seferlere Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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