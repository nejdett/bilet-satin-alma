<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireLogin();
if (SessionManager::hasRole('admin')) {
    http_response_code(403);
    header('Location: ../index.php');
    exit();
}
$userId = SessionManager::getCurrentUserId();
if ($userId == '78') {
    $userId = '78fdd259-df09-49bc-9575-117ba6f206e8';
}
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT * FROM User WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
        $userDetails = [
            'fullname' => $userData['full_name'] ?? 'Kullanıcı',
            'email' => $userData['email'] ?? 'Bilinmiyor',
            'balance' => $userData['balance'] ?? 0,
            'role' => $userData['role'] ?? 'user'
        ];
    } else {
        $userDetails = [
            'fullname' => $_SESSION['user_data']['fullname'] ?? 'Kullanıcı',
            'email' => $_SESSION['user_data']['email'] ?? 'Bilinmiyor',
            'balance' => $_SESSION['user_data']['balance'] ?? 0,
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
} catch (Exception $e) {
    $userDetails = [
        'fullname' => $_SESSION['user_data']['fullname'] ?? 'Kullanıcı',
        'email' => $_SESSION['user_data']['email'] ?? 'Bilinmiyor',
        'balance' => $_SESSION['user_data']['balance'] ?? 0,
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}
$companyInfo = null;
if (SessionManager::hasRole('company')) {
    $companyId = SessionManager::getCurrentCompanyId();
    if ($companyId) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
            $stmt->execute([$companyId]);
            $companyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
        }
    }
}
$ticket = new Ticket();
$userTickets = [];
if (!SessionManager::hasRole('company')) {
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->execute([$userDetails['email']]);
        $realUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($realUser) {
            $userTickets = $ticket->getUserTickets($realUser['id']);
        }
    } catch (Exception $e) {
    }
}
$pageTitle = 'Profil - Otobüs Bileti Satış Platformu';
include __DIR__ . '/../includes/header.php';
?>
<div class="main-content-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 40px; padding-bottom: 50px;">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold text-white mb-3" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                <i class="fas fa-user-circle me-3"></i>Profil
            </h1>
            <p class="lead text-white-50 mb-5" style="font-size: 1.3rem;">
                <?php if (SessionManager::hasRole('company')): ?>
                    Firma yetkilisi hesap bilgileriniz ve istatistikleriniz
                <?php else: ?>
                    Hesap bilgileriniz ve biletleriniz
                <?php endif; ?>
            </p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-lg border-0" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                        <div class="mb-2">
                            <i class="fas fa-user-circle fa-3x text-white"></i>
                        </div>
                        <h5 class="text-white mb-0 fw-bold">Profil Bilgileri</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Ad Soyad</label>
                            <p class="h6 mb-0"><?= htmlspecialchars($userDetails['fullname']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">E-posta</label>
                            <p class="h6 mb-0"><?= htmlspecialchars($userDetails['email']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Rol</label>
                            <p class="mb-0">
                                <?php if (SessionManager::hasRole('company')): ?>
                                    <span class="badge bg-warning px-3 py-2">Firma Yetkilisi</span>
                                <?php else: ?>
                                    <span class="badge bg-primary px-3 py-2">Kullanıcı</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (SessionManager::hasRole('company') && $companyInfo): ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Bağlı Firma</label>
                                <p class="h6 mb-0 text-primary"><?= htmlspecialchars($companyInfo['name']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Firma ID</label>
                                <p class="mb-0">
                                    <code class="bg-light px-2 py-1 rounded"><?= substr($companyInfo['id'], 0, 8) ?>...</code>
                                </p>
                            </div>
                        <?php endif; ?>
                        <?php if (!SessionManager::hasRole('company')): ?>
                            <div class="mb-0">
                                <label class="form-label fw-semibold text-muted">Bakiye</label>
                                <p class="h5 mb-0">
                                    <span class="text-success fw-bold"><?= number_format($userDetails['balance'], 2) ?> TL</span>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card shadow-lg border-0 mt-4" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-3" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 20px 20px 0 0;">
                        <h6 class="text-white mb-0 fw-bold">Hızlı İşlemler</h6>
                    </div>
                    <div class="card-body p-4">
                        <?php if (SessionManager::hasRole('company')): ?>
                            <a href="company_panel.php" class="btn btn-lg w-100 mb-3 fw-semibold" 
                               style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2); border: none;">
                                <i class="fas fa-building me-2"></i>Firma Paneli
                            </a>
                            <a href="company/trips.php" class="btn btn-outline-primary btn-lg w-100 mb-3 fw-semibold" 
                               style="border-radius: 15px;">
                                <i class="fas fa-bus me-2"></i>Sefer Yönetimi
                            </a>
                        <?php else: ?>
                            <a href="../index.php" class="btn btn-lg w-100 mb-3 fw-semibold" 
                               style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2); border: none;">
                                <i class="fas fa-search me-2"></i>Sefer Ara
                            </a>
                            <a href="my_tickets.php" class="btn btn-outline-primary btn-lg w-100 mb-3 fw-semibold" 
                               style="border-radius: 15px;">
                                <i class="fas fa-ticket-alt me-2"></i>Biletlerim
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <?php if (SessionManager::hasRole('company')): ?>
                    <div class="card shadow-lg border-0" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                            <div class="mb-2">
                                <i class="fas fa-chart-bar fa-2x text-white"></i>
                            </div>
                            <h5 class="text-white mb-0 fw-bold">Firma İstatistikleri</h5>
                            <p class="text-white-50 mb-0 mt-2">Firmanızın genel durumu</p>
                        </div>
                        <div class="card-body p-4">
                            <?php
                            $companyStats = ['trips' => 0, 'tickets' => 0, 'revenue' => 0];
                            if ($companyId) {
                                try {
                                    $pdo = new PDO('sqlite:' . __DIR__ . '/../bilet-satis-veritabani.db');
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    $currentDateTime = date('Y-m-d H:i:s');
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Trips WHERE company_id = ? AND departure_time > ?");
                                    $stmt->execute([$companyId, $currentDateTime]);
                                    $tripCount = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $companyStats['trips'] = $tripCount['count'] ?? 0;
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE tr.company_id = ?");
                                    $stmt->execute([$companyId]);
                                    $ticketCount = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $companyStats['tickets'] = $ticketCount['count'] ?? 0;
                                    $stmt = $pdo->prepare("SELECT SUM(tr.price) as total FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE tr.company_id = ?");
                                    $stmt->execute([$companyId]);
                                    $revenue = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $companyStats['revenue'] = $revenue['total'] ?? 0;
                                } catch (Exception $e) {
                                }
                            }
                            ?>
                            <div class="row text-center mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="p-4 rounded-3" style="background: linear-gradient(45deg, #007bff, #6610f2); color: white;">
                                        <i class="fas fa-bus fa-2x mb-2"></i>
                                        <h3 class="fw-bold mb-1"><?= number_format($companyStats['trips']) ?></h3>
                                        <small>Aktif Sefer</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-4 rounded-3" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                                        <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                                        <h3 class="fw-bold mb-1"><?= number_format($companyStats['tickets']) ?></h3>
                                        <small>Satılan Bilet</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-4 rounded-3" style="background: linear-gradient(45deg, #ffc107, #fd7e14); color: white;">
                                        <i class="fas fa-lira-sign fa-2x mb-2"></i>
                                        <h3 class="fw-bold mb-1"><?= number_format($companyStats['revenue'], 0) ?> ₺</h3>
                                        <small>Toplam Gelir</small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($companyInfo): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="fw-bold mb-3">Firma Detayları</h6>
                                        <div class="table-responsive">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td class="fw-semibold text-muted" style="width: 30%;">Firma Adı:</td>
                                                    <td><?= htmlspecialchars($companyInfo['name']) ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Firma ID:</td>
                                                    <td><code class="bg-light px-2 py-1 rounded"><?= $companyInfo['id'] ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Kayıt Tarihi:</td>
                                                    <td><?= isset($companyInfo['created_at']) ? date('d.m.Y', strtotime($companyInfo['created_at'])) : 'Bilinmiyor' ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Durum:</td>
                                                    <td><span class="badge bg-success">Aktif</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-lg border-0" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                            <div class="mb-2">
                                <i class="fas fa-ticket-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="text-white mb-0 fw-bold">Biletlerim</h5>
                            <p class="text-white-50 mb-0 mt-2">Satın aldığınız biletler</p>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($userTickets)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-ticket-alt fa-4x text-muted mb-4"></i>
                                    <h4 class="fw-bold mb-3">Henüz biletiniz bulunmuyor</h4>
                                    <p class="text-muted mb-4 lead">İlk biletinizi almak için sefer arayın.</p>
                                    <a href="../index.php" class="btn btn-lg fw-semibold px-5" 
                                       style="border-radius: 15px; background: linear-gradient(45deg, #28a745, #20c997); border: none;">
                                        <i class="fas fa-search me-2"></i>Sefer Ara
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-bold">Güzergah</th>
                                                <th class="fw-bold">Tarih</th>
                                                <th class="fw-bold">Koltuk</th>
                                                <th class="fw-bold">Fiyat</th>
                                                <th class="fw-bold">Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($userTickets as $ticketData): ?>
                                                <?php 
                                                $isPast = strtotime($ticketData['departure_time']) < time();
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($ticketData['departure_city']) ?></strong>
                                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                                        <strong><?= htmlspecialchars($ticketData['destination_city']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= date('d.m.Y', strtotime($ticketData['departure_time'])) ?></div>
                                                        <small class="text-muted"><?= date('H:i', strtotime($ticketData['departure_time'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info px-3 py-2"><?= $ticketData['seat_number'] ?? 'N/A' ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $displayPrice = $ticketData['final_price'] ?? $ticketData['trip_price'];
                                                        $isDiscounted = isset($ticketData['final_price']) && $ticketData['final_price'] != $ticketData['trip_price'];
                                                        ?>
                                                        <strong class="text-success"><?= number_format($displayPrice, 2) ?> TL</strong>
                                                        <?php if ($isDiscounted): ?>
                                                            <br><small class="text-success"><i class="fas fa-tag me-1"></i>İndirimli!</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($isPast): ?>
                                                            <span class="badge bg-secondary px-3 py-2">Tamamlandı</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success px-3 py-2">Aktif</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<style>
    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        min-height: 100vh;
    }
    .main-content-wrapper {
        min-height: 100vh;
    }
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
    }
    .btn {
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    @media (max-width: 768px) {
        .main-content-wrapper {
            padding-top: 80px;
            padding-bottom: 30px;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .display-4 {
            font-size: 2.5rem;
        }
    }
</style>
</div>
<footer class="text-white py-4" style="background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-2">
                    <i class="fas fa-bus me-2"></i>Otobüs Bileti Platformu
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 200 * index);
        });
    });
</script>
</body>
</html>