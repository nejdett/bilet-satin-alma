<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$pageTitle = 'Sefer Yönetimi - Admin Paneli';
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $trips = $db->query('
        SELECT t.*, bc.name as company_name,
               COUNT(tk.id) as sold_tickets,
               (t.capacity - COUNT(tk.id)) as remaining_seats
        FROM Trips t
        LEFT JOIN Bus_Company bc ON t.company_id = bc.id
        LEFT JOIN Tickets tk ON t.id = tk.trip_id
        WHERE t.departure_time > datetime("now")
        GROUP BY t.id
        ORDER BY t.departure_time ASC
    ')->fetchAll();
} catch (Exception $e) {
    $trips = [];
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
                <li class="breadcrumb-item active text-white-50">Sefer Yönetimi</li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="text-white fw-bold">
                    <i class="fas fa-route me-2"></i>Sefer Yönetimi
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-inline-block position-relative">
                    <div class="card border-0 shadow" style="border-radius: 15px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <div class="card-body px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                        <i class="fas fa-route text-white fa-sm"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-white fw-bold mb-0"><?php echo count($trips); ?></h4>
                                    <small class="text-white-50">Aktif Sefer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute top-0 end-0 translate-middle">
                        <span class="badge rounded-pill" style="background: linear-gradient(45deg, #28a745, #20c997); font-size: 0.7rem; padding: 4px 8px;">
                            <i class="fas fa-clock me-1"></i>Canlı
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($trips)): ?>
            <div class="row">
                <?php foreach ($trips as $trip): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100" style="border-radius: 25px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.1)'">
                            <div class="card-header text-center py-4 position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 25px 25px 0 0; overflow: hidden;">
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);"></div>
                                <div class="position-relative">
                                    <div class="mb-3">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                                            <i class="fas fa-bus fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-white mb-2 fw-bold"><?php echo htmlspecialchars($trip['company_name']); ?></h5>
                                    <div class="badge bg-light text-dark px-3 py-2 rounded-pill" style="font-size: 0.9rem;">
                                        <i class="fas fa-chair me-1"></i><?php echo $trip['capacity']; ?> Koltuk
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row mb-4">
                                    <div class="col-6 text-center">
                                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                                            <i class="fas fa-map-marker-alt text-primary fa-lg mb-2"></i>
                                            <h6 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($trip['departure_city']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo date('d.m H:i', strtotime($trip['departure_time'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #e8f5e8, #c8e6c9);">
                                            <i class="fas fa-map-marker-alt text-success fa-lg mb-2"></i>
                                            <h6 class="fw-bold text-success mb-1"><?php echo htmlspecialchars($trip['destination_city']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo date('d.m H:i', strtotime($trip['arrival_time'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-4 text-center">
                                        <div class="p-2">
                                            <h5 class="text-success fw-bold mb-1"><?php echo number_format($trip['price'], 0); ?>₺</h5>
                                            <small class="text-muted">
                                                <i class="fas fa-lira-sign me-1"></i>Fiyat
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-2">
                                            <h5 class="text-primary fw-bold mb-1"><?php echo $trip['sold_tickets']; ?></h5>
                                            <small class="text-muted">
                                                <i class="fas fa-ticket-alt me-1"></i>Satılan
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="p-2">
                                            <h5 class="text-info fw-bold mb-1"><?php echo $trip['remaining_seats']; ?></h5>
                                            <small class="text-muted">
                                                <i class="fas fa-chair me-1"></i>Kalan
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted fw-bold">Doluluk Oranı</small>
                                        <small class="text-muted fw-bold">
                                            <?php 
                                            $occupancyRate = ($trip['sold_tickets'] / $trip['capacity']) * 100;
                                            echo round($occupancyRate, 1); 
                                            ?>%
                                        </small>
                                    </div>
                                    <div class="progress" style="height: 8px; border-radius: 10px;">
                                        <?php 
                                        $progressClass = $occupancyRate >= 80 ? 'bg-danger' : ($occupancyRate >= 60 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <div class="progress-bar <?php echo $progressClass; ?>" 
                                             style="width: <?php echo $occupancyRate; ?>%; border-radius: 10px; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <a href="<?php echo getAbsoluteUrl('pages/admin/trip_details.php?id=' . $trip['id']); ?>" 
                                       class="btn btn-primary btn-lg w-100 rounded-pill" 
                                       style="background: linear-gradient(45deg, #007bff, #6610f2); border: none; box-shadow: 0 4px 15px rgba(0,123,255,0.3);">
                                        <i class="fas fa-eye me-2"></i>Detayları Görüntüle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-route fa-5x text-muted mb-4"></i>
                            <h4 class="fw-bold mb-3">Henüz sefer bulunmuyor</h4>
                            <p class="text-muted">Sistemde kayıtlı sefer bulunmamaktadır.</p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
