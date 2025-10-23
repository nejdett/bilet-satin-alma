<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
SessionManager::startSession();
SessionManager::requireLogin();
$pageTitle = 'Biletlerim - Otobüs Bileti Satış Platformu';
$ticket = new Ticket();
$userId = SessionManager::getCurrentUserId();
if ($userId == '78') {
    $userId = '78fdd259-df09-49bc-9575-117ba6f206e8';
}
$userTickets = $ticket->getUserTickets($userId);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.min.css?v=1.1" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php if (isset($_GET['booking_success']) && $_GET['booking_success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Tebrikler!</strong> 
            <?php 
            if (isset($_GET['ticket_ids'])) {
                $ticketIds = explode(',', $_GET['ticket_ids']);
                $ticketCount = count($ticketIds);
                echo $ticketCount > 1 ? "{$ticketCount} adet biletiniz" : "Biletiniz";
            } else {
                echo "Biletiniz";
            }
            ?> başarıyla satın alındı.
            <?php if (isset($_GET['ticket_id'])): ?>
                <br><small>Bilet ID: <?= htmlspecialchars($_GET['ticket_id']) ?></small>
            <?php elseif (isset($_GET['ticket_ids'])): ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] == 'ticket_cancelled'): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Başarılı!</strong> Biletiniz iptal edildi.
            <?php if (isset($_GET['refund'])): ?>
                <br><small><?= number_format($_GET['refund'], 2) ?> TL bakiyenize eklendi.</small>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Hata!</strong>
            <?php 
            switch($_GET['error']) {
                case 'cannot_cancel':
                    echo 'Bilet iptal edilemez. Kalkış saatine 1 saatten az kaldı.';
                    break;
                case 'cancel_failed':
                    echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Bilet iptal edilemedi.';
                    break;
                case 'invalid_ticket':
                    echo 'Geçersiz bilet.';
                    break;
                default:
                    echo 'Bir hata oluştu.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="main-content-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-white mb-3" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    <i class="fas fa-ticket-alt me-3"></i>Biletlerim
                </h1>
                <p class="lead text-white-50 mb-5" style="font-size: 1.3rem;">
                    Satın aldığınız biletleri görüntüleyin ve yönetin
                </p>
            </div>
            <?php if (empty($userTickets)): ?>
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                            <div class="card-body text-center p-5">
                                <div class="mb-4">
                                    <i class="fas fa-ticket-alt text-muted fa-5x"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Henüz biletiniz bulunmuyor</h4>
                                <p class="text-muted mb-4 lead">
                                    İlk seyahatinizi planlamak için sefer arayabilirsiniz.
                                </p>
                                <div class="d-grid">
                                    <a href="../index.php" class="btn btn-primary btn-lg px-5 fw-semibold" 
                                       style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                        <i class="fas fa-search me-2"></i>Sefer Ara
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-5">
                    <?php foreach ($userTickets as $ticketData): ?>
                        <?php 
                        $isPast = strtotime($ticketData['departure_time']) < time();
                        $canCancel = $ticket->canCancelTicket($ticketData['id']);
                        $departureDate = new DateTime($ticketData['departure_time']);
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95); transition: all 0.3s ease;">
                                <div class="card-header text-center py-4" style="background: linear-gradient(45deg, <?php echo $isPast ? '#6c757d, #495057' : '#007bff, #6610f2'; ?>); border-radius: 20px 20px 0 0;">
                                    <div class="mb-2">
                                        <i class="fas fa-bus fa-2x text-white"></i>
                                    </div>
                                    <h6 class="text-white mb-0 fw-bold">
                                        Bilet #<?php echo substr($ticketData['id'], -6); ?>
                                    </h6>
                                    <span class="badge <?php echo $isPast ? 'bg-light text-dark' : 'bg-success'; ?> mt-2">
                                        <?php echo $isPast ? 'Tamamlandı' : 'Aktif'; ?>
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-center">
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($ticketData['departure_city']); ?></h6>
                                                <small class="text-muted">Kalkış</small>
                                            </div>
                                            <div class="flex-grow-1 text-center">
                                                <i class="fas fa-arrow-right text-primary fa-lg"></i>
                                            </div>
                                            <div class="text-center">
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($ticketData['destination_city']); ?></h6>
                                                <small class="text-muted">Varış</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar text-primary me-2"></i>
                                                <div>
                                                    <small class="text-muted d-block">Tarih</small>
                                                    <span class="fw-bold"><?php echo $departureDate->format('d.m.Y'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-clock text-primary me-2"></i>
                                                <div>
                                                    <small class="text-muted d-block">Saat</small>
                                                    <span class="fw-bold"><?php echo $departureDate->format('H:i'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-chair text-info me-2"></i>
                                                <div>
                                                    <small class="text-muted d-block">Koltuk</small>
                                                    <span class="badge bg-info"><?php echo $ticketData['seat_number'] ?? 'N/A'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-lira-sign text-success me-2"></i>
                                                <div>
                                                    <small class="text-muted d-block">Fiyat</small>
                                                    <?php 
                                                    $displayPrice = $ticketData['final_price'] ?? $ticketData['trip_price'];
                                                    $isDiscounted = isset($ticketData['final_price']) && $ticketData['final_price'] != $ticketData['trip_price'];
                                                    ?>
                                                    <span class="fw-bold text-success"><?php echo number_format($displayPrice, 2); ?> TL</span>
                                                    <?php if ($isDiscounted): ?>
                                                        <br><small class="text-success"><i class="fas fa-tag me-1"></i>İndirimli!</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (isset($ticketData['company_name'])): ?>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building text-warning me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Firma</small>
                                                <span><?php echo htmlspecialchars($ticketData['company_name']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent p-4">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm flex-fill fw-semibold" 
                                                onclick="viewTicketDetails('<?php echo $ticketData['id']; ?>')"
                                                style="border-radius: 10px;">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        <a href="download_ticket.php?ticket=<?php echo $ticketData['id']; ?>" 
                                           class="btn btn-outline-success btn-sm flex-fill fw-semibold" 
                                           target="_blank" style="border-radius: 10px;">
                                            <i class="fas fa-download me-1"></i>İndir
                                        </a>
                                        <?php if ($canCancel): ?>
                                            <button class="btn btn-outline-danger btn-sm fw-semibold" 
                                                    onclick="cancelTicket('<?php echo $ticketData['id']; ?>')"
                                                    style="border-radius: 10px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <footer class="text-white py-4" style="background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-2">Otobüs Bileti Platformu</h5>
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
        @media (max-width: 768px) {
            .main-content-wrapper {
                padding-top: 100px;
                padding-bottom: 30px;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .display-4 {
                font-size: 2.5rem;
            }
        }
    </style>
    <script>
        function viewTicketDetails(ticketId) {
            alert('Bilet detayları: ' + ticketId);
        }
        function cancelTicket(ticketId) {
            if (confirm('Bu bileti iptal etmek istediğinizden emin misiniz?\n\nİptal edilen biletler geri alınamaz.')) {
                window.location.href = 'cancel_ticket.php?id=' + ticketId;
            }
        }
    </script>
</body>
</html>