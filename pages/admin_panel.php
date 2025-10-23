<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$pageTitle = 'Admin Paneli - Sistem Yönetimi';
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $userCount = $db->query('SELECT COUNT(*) FROM User')->fetchColumn();
    $adminCount = $db->query('SELECT COUNT(*) FROM User WHERE role = "admin"')->fetchColumn();
    $companyUserCount = $db->query('SELECT COUNT(*) FROM User WHERE role = "company"')->fetchColumn();
    $regularUserCount = $db->query('SELECT COUNT(*) FROM User WHERE role = "user"')->fetchColumn();
    $tripCount = $db->query('SELECT COUNT(*) FROM Trips')->fetchColumn();
    $activeTripCount = $db->query('SELECT COUNT(*) FROM Trips WHERE departure_time > datetime("now")')->fetchColumn();
    $pastTripCount = $db->query('SELECT COUNT(*) FROM Trips WHERE departure_time <= datetime("now")')->fetchColumn();
    $ticketCount = $db->query('SELECT COUNT(*) FROM Tickets')->fetchColumn();
    $totalRevenue = $db->query('SELECT COALESCE(SUM(total_price), 0) FROM Tickets')->fetchColumn();
    $companyCount = $db->query('SELECT COUNT(*) FROM Bus_Company')->fetchColumn();
    $recentUsers = $db->query('SELECT id, full_name, email, role, created_at FROM User ORDER BY created_at DESC LIMIT 10')->fetchAll();
    $recentTrips = $db->query('
        SELECT t.*, bc.name as company_name 
        FROM Trips t 
        LEFT JOIN Bus_Company bc ON t.company_id = bc.id 
        ORDER BY t.created_date DESC 
        LIMIT 10
    ')->fetchAll();
    $recentTickets = $db->query('
        SELECT tk.*, t.departure_city, t.destination_city, u.full_name, u.email,
               t.departure_time, bc.name as company_name
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        JOIN User u ON tk.user_id = u.id
        LEFT JOIN Bus_Company bc ON t.company_id = bc.id
        ORDER BY tk.created_at DESC
        LIMIT 10
    ')->fetchAll();
} catch (Exception $e) {
    $userCount = $adminCount = $companyUserCount = $regularUserCount = 0;
    $tripCount = $activeTripCount = $pastTripCount = 0;
    $ticketCount = $totalRevenue = $companyCount = 0;
    $recentUsers = $recentTrips = $recentTickets = [];
}
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-panel-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="<?php echo getAbsoluteUrl('index.php'); ?>" class="text-white text-decoration-none">
                        <i class="fas fa-home me-1"></i>Ana Sayfa
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-cogs me-1"></i>Admin Paneli
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #dc3545, #fd7e14); border-radius: 20px 20px 0 0;">
                        <div class="mb-2">
                            <i class="fas fa-cogs fa-3x text-white"></i>
                        </div>
                        <h2 class="text-white mb-0 fw-bold">Admin Paneli</h2>
                        <p class="text-white-50 mb-0 mt-2">Sistem Yönetimi ve İstatistikler</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #0056b3);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($userCount); ?></h3>
                        <p class="mb-1">Toplam Kullanıcı</p>
                        <small class="text-white-50">
                            Admin: <?php echo $adminCount; ?> | 
                            Firma: <?php echo $companyUserCount; ?> | 
                            Normal: <?php echo $regularUserCount; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #28a745, #1e7e34);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-bus fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($tripCount); ?></h3>
                        <p class="mb-1">Toplam Sefer</p>
                        <small class="text-white-50">
                            Aktif: <?php echo $activeTripCount; ?> | 
                            Geçmiş: <?php echo $pastTripCount; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #ffc107, #e0a800);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($ticketCount); ?></h3>
                        <p class="mb-1">Satılan Bilet</p>
                        <small class="text-white-50">
                            Toplam Gelir: <?php echo number_format($totalRevenue, 2); ?> ₺
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; background: linear-gradient(45deg, #6f42c1, #5a32a3);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-building fa-3x mb-3"></i>
                        <h3 class="fw-bold"><?php echo number_format($companyCount); ?></h3>
                        <p class="mb-1">Otobüs Firması</p>
                        <small class="text-white-50">
                            Kayıtlı firma sayısı
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-user-plus me-2"></i>Son Kullanıcılar
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $index => $user): ?>
                                <?php if ($index > 0): ?>
                                    <hr class="my-0" style="border-color: rgba(0, 0, 0, 0.1);">
                                <?php endif; ?>
                                <div class="p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'company' ? 'bg-warning' : 'bg-primary'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p class="mb-0">Henüz kullanıcı yok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #28a745, #1e7e34); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-bus me-2"></i>Son Seferler
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentTrips)): ?>
                            <?php foreach ($recentTrips as $index => $trip): ?>
                                <?php if ($index > 0): ?>
                                    <hr class="my-0" style="border-color: rgba(0, 0, 0, 0.1);">
                                <?php endif; ?>
                                <div class="p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(45deg, #28a745, #1e7e34); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-bus text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($trip['departure_city']); ?> → <?php echo htmlspecialchars($trip['destination_city']); ?>
                                            </h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor'); ?></p>
                                            <span class="badge bg-success"><?php echo number_format($trip['price'], 2); ?> ₺</span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-bus fa-3x mb-3"></i>
                                <p class="mb-0">Henüz sefer yok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #ffc107, #e0a800); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-ticket-alt me-2"></i>Son Biletler
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
                                            <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(45deg, #ffc107, #e0a800); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-ticket-alt text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($ticket['departure_city']); ?> → <?php echo htmlspecialchars($ticket['destination_city']); ?>
                                            </h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($ticket['full_name']); ?></p>
                                            <span class="badge bg-warning"><?php echo number_format($ticket['total_price'], 2); ?> ₺</span>
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
                                <p class="mb-0">Henüz bilet yok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #6f42c1, #5a32a3); border-radius: 20px 20px 0 0;">
                        <h5 class="text-white mb-0 fw-bold">
                            <i class="fas fa-tools me-2"></i>Hızlı İşlemler
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="<?php echo getAbsoluteUrl('pages/admin/users.php'); ?>" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #007bff, #0056b3); border: none; border-radius: 15px; color: white; text-decoration: none; min-height: 100px;">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <span class="fw-semibold">Kullanıcı Yönetimi</span>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="<?php echo getAbsoluteUrl('pages/admin/trips.php'); ?>" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #28a745, #1e7e34); border: none; border-radius: 15px; color: white; text-decoration: none; min-height: 100px;">
                                    <i class="fas fa-bus fa-2x mb-2"></i>
                                    <span class="fw-semibold">Sefer Yönetimi</span>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="<?php echo getAbsoluteUrl('pages/admin/companies.php'); ?>" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #ffc107, #e0a800); border: none; border-radius: 15px; color: white; text-decoration: none; min-height: 100px;">
                                    <i class="fas fa-building fa-2x mb-2"></i>
                                    <span class="fw-semibold">Firma Yönetimi</span>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="<?php echo getAbsoluteUrl('pages/admin/coupons.php'); ?>" class="btn btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" 
                                   style="background: linear-gradient(45deg, #e83e8c, #dc3545); border: none; border-radius: 15px; color: white; text-decoration: none; min-height: 100px;">
                                    <i class="fas fa-tags fa-2x mb-2"></i>
                                    <span class="fw-semibold">İndirim Kuponları</span>
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