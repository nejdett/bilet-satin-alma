<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Trip.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Coupon.php';
require_once __DIR__ . '/../classes/User.php';
SessionManager::startSession();
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php?redirect=booking');
    exit;
}
$currentUser = SessionManager::getCurrentUser();
$tripId = $_GET['trip_id'] ?? null;
if (!$tripId) {
    header('Location: search.php');
    exit;
}
$trip = new Trip();
$ticket = new Ticket();
$coupon = new Coupon();
$user = new User();
// Fetch trip information
$tripDetails = $trip->getTripById($tripId);
if (!$tripDetails) {
    header('Location: search.php');
    exit;
}
// Get current user info
$userId = SessionManager::getCurrentUserId();
$userDetails = $user->getUserById($userId);
// Fallback to session data if user not found in DB
if (!$userDetails) {
    $sessionUserData = SessionManager::getCurrentUser();
    if ($sessionUserData && isset($sessionUserData['balance'])) {
        $userDetails = [
            'id' => $userId,
            'fullname' => $sessionUserData['fullname'] ?? 'Kullanıcı',
            'email' => $sessionUserData['email'] ?? '',
            'balance' => $sessionUserData['balance'] ?? 0,
            'role' => SessionManager::getCurrentUserRole()
        ];
    } else {
        SessionManager::setFlashMessage('Kullanıcı bilgileri bulunamadı. Lütfen tekrar giriş yapın.', 'error');
        header('Location: login.php');
        exit;
    }
}
$availableSeats = $ticket->getAvailableSeats($tripId);
$bookingResult = null;
$couponResult = null;
$selectedSeats = [];
$couponCode = '';
$couponResult = null;
$discountAmount = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSeats = $_POST['seat_numbers'] ?? [];
    $couponCode = trim($_POST['coupon_code'] ?? '');
    if (isset($_POST['apply_coupon']) && !empty($couponCode)) {
        $totalPrice = count($selectedSeats) * $tripDetails['price'];
        $couponResult = $coupon->applyCoupon($couponCode, $totalPrice, $tripDetails['company_id']);
        if ($couponResult['success']) {
            $discountAmount = $couponResult['discountAmount'];
        }
    }
    if (!empty($selectedSeats)) {
        if (!empty($couponCode)) {
            $totalPrice = count($selectedSeats) * $tripDetails['price'];
            $couponResult = $coupon->applyCoupon($couponCode, $totalPrice, $tripDetails['company_id']);
            if ($couponResult['success']) {
                $discountAmount = $couponResult['discountAmount'];
            }
        }
        if (isset($_POST['confirm_booking'])) {
            $bookingResult = $ticket->purchaseMultipleTickets(
                $tripId, 
                $userId, 
                $selectedSeats,
                !empty($couponCode) ? $couponCode : null
            );
            error_log("Booking result: " . json_encode($bookingResult));
            if ($bookingResult['success']) {
                $userRole = SessionManager::getCurrentUserRole();
                $ticketParam = implode(',', $bookingResult['ticketIds']);
                if ($userRole === 'user') {
                    header('Location: my_tickets.php?booking_success=1&ticket_ids=' . urlencode($ticketParam));
                } else {
                    header('Location: profile.php?booking_success=1&ticket_ids=' . urlencode($ticketParam));
                }
                exit;
            } else {
                $errorMessage = $bookingResult['message'] ?? 'Bilinmeyen bir hata oluştu.';
            }
        }
    } else {
        if (isset($_POST['confirm_booking'])) {
            $errorMessage = 'Lütfen en az bir koltuk seçin.';
        }
    }
}
$pageTitle = 'Bilet Rezervasyonu';
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
<?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="main-content-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 100px; padding-bottom: 50px;">
        <div class="container">
            <div class="text-center mb-3">
                <h1 class="display-4 fw-bold text-white mb-2" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    <i class="fas fa-ticket-alt me-3"></i>Bilet Rezervasyonu
                </h1>
                <p class="lead text-white-50 mb-3" style="font-size: 1.3rem;">
                    Koltuk seçimi yapın ve rezervasyonunuzu tamamlayın
                </p>
            </div>
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                        <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                            <div class="mb-2">
                                <i class="fas fa-bus fa-2x text-white"></i>
                            </div>
                            <h3 class="text-white mb-0 fw-bold">Sefer Detayları</h3>
                        </div>
                        <div class="card-body p-5">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="feature-icon me-3">
                                            <i class="fas fa-map-marker-alt fa-2x text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Kalkış</h6>
                                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($tripDetails['departure_city']) ?></h5>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon me-3">
                                            <i class="fas fa-map-marker-alt fa-2x text-danger"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Varış</h6>
                                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($tripDetails['destination_city']) ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="feature-icon me-3">
                                            <i class="fas fa-calendar fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Kalkış Tarihi & Saati</h6>
                                            <h5 class="mb-0 fw-bold"><?= date('d.m.Y H:i', strtotime($tripDetails['departure_time'])) ?></h5>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon me-3">
                                            <i class="fas fa-clock fa-2x text-info"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Varış Tarihi & Saati</h6>
                                            <h5 class="mb-0 fw-bold"><?= date('d.m.Y H:i', strtotime($tripDetails['arrival_time'])) ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon me-3">
                                            <span style="font-size: 2rem; color: #28a745;">₺</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Bilet Fiyatı</h6>
                                            <h4 class="mb-0 fw-bold text-success"><?= number_format($tripDetails['price'], 2) ?> TL</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon me-3">
                                            <i class="fas fa-chair fa-2x text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">Müsait Koltuk</h6>
                                            <h4 class="mb-0 fw-bold text-warning"><?= count($availableSeats) ?> / <?= $tripDetails['capacity'] ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (empty($availableSeats)): ?>
                        <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                            <div class="card-body text-center p-5">
                                <div class="mb-4">
                                    <i class="fas fa-exclamation-triangle text-warning fa-5x"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Müsait Koltuk Bulunmuyor</h4>
                                <p class="text-muted mb-4 lead">
                                    Bu sefer için tüm koltuklar dolu. Başka bir sefer arayabilirsiniz.
                                </p>
                                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                                    <a href="search.php" class="btn btn-primary btn-lg px-5 fw-semibold" 
                                       style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                        <i class="fas fa-search me-2"></i>Başka Sefer Ara
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-lg-8 mb-4">
                                <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 20px 20px 0 0;">
                                        <div class="mb-2">
                                            <i class="fas fa-chair fa-2x text-white"></i>
                                        </div>
                                        <h4 class="text-white mb-0 fw-bold">Koltuk Seçimi</h4>
                                        <p class="text-white-50 mb-0 mt-2">Birden fazla koltuk seçebilirsiniz</p>
                                    </div>
                                    <div class="card-body p-5">
                                        <form method="POST" id="bookingForm">
                                            <div class="text-center mb-4">
                                                <div class="d-flex justify-content-center gap-4 flex-wrap">
                                                    <div class="d-flex align-items-center">
                                                        <div class="seat-legend available me-2"></div>
                                                        <span class="fw-semibold">Müsait</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <div class="seat-legend selected me-2"></div>
                                                        <span class="fw-semibold">Seçili</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <div class="seat-legend occupied me-2"></div>
                                                        <span class="fw-semibold">Dolu</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="seats-container">
                                                <?php
                                                $seatsPerRow = 4;
                                                $totalSeats = $tripDetails['capacity'];
                                                for ($i = 1; $i <= $totalSeats; $i++):
                                                    $isAvailable = in_array($i, $availableSeats);
                                                    if (($i - 1) % $seatsPerRow == 0 && $i > 1) {
                                                        echo '</div><div class="seat-row mb-3">';
                                                    } elseif ($i == 1) {
                                                        echo '<div class="seat-row mb-3">';
                                                    }
                                                ?>
                                                    <div class="seat-item">
                                                        <?php if ($isAvailable): ?>
                                                            <input type="checkbox" 
                                                                   name="seat_numbers[]" 
                                                                   value="<?= $i ?>" 
                                                                   id="seat_<?= $i ?>"
                                                                   class="seat-checkbox"
                                                                   onchange="updateBookingSummary()">
                                                            <label for="seat_<?= $i ?>" class="seat-label available">
                                                                <?= $i ?>
                                                            </label>
                                                        <?php else: ?>
                                                            <span class="seat-label occupied"><?= $i ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php 
                                                    if ($i == $totalSeats) {
                                                        echo '</div>';
                                                    }
                                                endfor; 
                                                ?>
                                            </div>
                                            <div class="coupon-section mt-5">
                                                <h5 class="fw-bold mb-3">
                                                    <i class="fas fa-ticket-alt text-primary me-2"></i>
                                                    İndirim Kuponu
                                                </h5>
                                                <div class="row">
                                                    <div class="col-md-8 mb-3">
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-end-0" style="border-radius: 15px 0 0 15px;">
                                                                <i class="fas fa-tag text-primary"></i>
                                                            </span>
                                                            <input type="text" 
                                                                   class="form-control form-control-lg border-start-0" 
                                                                   name="coupon_code" 
                                                                   id="coupon_code"
                                                                   placeholder="Kupon kodunu girin (opsiyonel)"
                                                                   value="<?= htmlspecialchars($couponCode) ?>"
                                                                   style="border-radius: 0 15px 15px 0;">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="submit" class="btn btn-outline-primary btn-lg w-100 fw-semibold" 
                                                                name="apply_coupon" style="border-radius: 15px;">
                                                            <i class="fas fa-check me-2"></i>Kuponu Uygula
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php if ($couponResult): ?>
                                                    <?php if ($couponResult['success']): ?>
                                                        <div class="alert alert-success mt-3" style="border-radius: 15px;">
                                                            <i class="fas fa-check-circle me-2"></i>
                                                            <strong>Kupon uygulandı!</strong> %<?= $couponResult['discountRate'] ?> indirim
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-danger mt-3" style="border-radius: 15px;">
                                                            <i class="fas fa-exclamation-circle me-2"></i>
                                                            <?= htmlspecialchars($couponResult['message']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="booking-actions mt-5">
                                                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                                                    <button type="submit" 
                                                            name="confirm_booking" 
                                                            class="btn btn-success btn-lg px-5 fw-semibold"
                                                            id="confirmBookingBtn"
                                                            style="border-radius: 15px; background: linear-gradient(45deg, #28a745, #20c997);">
                                                        <i class="fas fa-credit-card me-2"></i>
                                                        <span class="btn-text">Rezervasyonu Onayla</span>
                                                        <div class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                                            <span class="visually-hidden">İşleniyor...</span>
                                                        </div>
                                                    </button>
                                                    <a href="search.php" class="btn btn-outline-light btn-lg px-5 fw-semibold" 
                                                       style="border-radius: 15px; border-color: #6610f2; color: #6610f2;">
                                                        <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border-0 shadow-lg sticky-top" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95); top: 20px;">
                                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #6610f2, #007bff); border-radius: 20px 20px 0 0;">
                                        <div class="mb-2">
                                            <i class="fas fa-receipt fa-2x text-white"></i>
                                        </div>
                                        <h5 class="text-white mb-0 fw-bold">Rezervasyon Özeti</h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div id="booking-summary">
                                            <div class="mb-3">
                                                <h6 class="fw-bold mb-2">Seçili Koltuklar:</h6>
                                                <div id="selected-seats-display" class="text-muted">Henüz koltuk seçilmedi</div>
                                            </div>
                                            <hr>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span>Koltuk Sayısı:</span>
                                                    <span id="seat-count" class="fw-bold">0</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Bilet Fiyatı:</span>
                                                    <span class="fw-bold"><?= number_format($tripDetails['price'], 2) ?> TL</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Ara Toplam:</span>
                                                    <span id="subtotal" class="fw-bold">0.00 TL</span>
                                                </div>
                                            </div>
                                            <div id="discount-section" class="mb-3 <?= $discountAmount > 0 ? '' : 'd-none' ?>">
                                                <div class="d-flex justify-content-between text-success">
                                                    <span>İndirim:</span>
                                                    <span id="discount-amount" class="fw-bold">-<?= number_format($discountAmount, 2) ?> TL</span>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between">
                                                    <h5 class="fw-bold">Toplam:</h5>
                                                    <h5 id="total-price" class="fw-bold text-primary">0.00 TL</h5>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold">Mevcut Bakiye:</span>
                                                    <span class="fw-bold <?= $userDetails['balance'] >= $finalPrice ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($userDetails['balance'], 2) ?> TL
                                                    </span>
                                                </div>
                                            </div>
                                            <div id="balance-warning" class="alert alert-warning d-none" style="border-radius: 10px;">
                                                <small><i class="fas fa-exclamation-triangle me-1"></i> Yetersiz bakiye!</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
    <?php if (isset($errorMessage)): ?>
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Rezervasyon Hatası
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tamam</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
        .feature-icon i {
            transition: all 0.3s ease;
        }
        .card:hover .feature-icon i {
            transform: scale(1.1);
        }
        /* Seat Styles */
        .seat-checkbox {
            display: none;
        }
        .seat-label {
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border: 3px solid #ddd;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            margin: 2px;
        }
        .seat-label.available {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-color: #28a745;
        }
        .seat-label.available:hover {
            background: linear-gradient(45deg, #218838, #1e7e34);
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        .seat-checkbox:checked + .seat-label.available {
            background: linear-gradient(45deg, #007bff, #6610f2) !important;
            border-color: #007bff !important;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        .seat-label.occupied {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border-color: #dc3545;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .seat-legend {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
        }
        .seat-legend.available {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        .seat-legend.selected {
            background: linear-gradient(45deg, #007bff, #6610f2);
        }
        .seat-legend.occupied {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        .seats-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .seat-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .seat-item {
            margin: 2px;
        }
        .sticky-top {
            top: 20px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6610f2;
            box-shadow: 0 0 0 0.2rem rgba(102, 16, 242, 0.25);
        }
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
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
            .seat-label {
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 12px;
            }
            .seats-container {
                max-width: 320px;
            }
        }
    </style>
    <script>
        const ticketPrice = <?= $tripDetails['price'] ?>;
        const userBalance = <?= $userDetails['balance'] ?>;
        const appliedDiscount = <?= $discountAmount ?>;
        const discountRate = <?= isset($couponResult) && $couponResult['success'] ? $couponResult['discountRate'] : 0 ?>;
        const selectedSeatsFromServer = <?= json_encode($selectedSeats) ?>;
        function updateBookingSummary() {
            const selectedSeats = document.querySelectorAll('input[name="seat_numbers[]"]:checked');
            const confirmBtn = document.getElementById('confirmBookingBtn');
            const selectedSeatsDisplay = document.getElementById('selected-seats-display');
            const seatCount = document.getElementById('seat-count');
            const subtotal = document.getElementById('subtotal');
            const totalPrice = document.getElementById('total-price');
            const balanceWarning = document.getElementById('balance-warning');
            const seatNumbers = Array.from(selectedSeats).map(seat => seat.value);
            const count = seatNumbers.length;
            const subtotalAmount = count * ticketPrice;
            let totalAmount = subtotalAmount;
            if (discountRate > 0 && count > 0) {
                const discountAmount = (subtotalAmount * discountRate) / 100;
                totalAmount = subtotalAmount - discountAmount;
                const discountSection = document.getElementById('discount-section');
                const discountAmountElement = document.getElementById('discount-amount');
                if (discountSection && discountAmountElement) {
                    discountSection.classList.remove('d-none');
                    discountAmountElement.textContent = '-' + discountAmount.toFixed(2) + ' TL';
                }
            }
            if (count > 0) {
                selectedSeatsDisplay.innerHTML = seatNumbers.map(num => 
                    `<span class="badge bg-primary me-1">${num}</span>`
                ).join('');
                confirmBtn.disabled = false;
            } else {
                selectedSeatsDisplay.textContent = 'Henüz koltuk seçilmedi';
                confirmBtn.disabled = true;
            }
            seatCount.textContent = count;
            subtotal.textContent = subtotalAmount.toFixed(2) + ' TL';
            totalPrice.textContent = totalAmount.toFixed(2) + ' TL';
            if (totalAmount > userBalance && count > 0) {
                balanceWarning.classList.remove('d-none');
                confirmBtn.disabled = true;
            } else {
                balanceWarning.classList.add('d-none');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            if (selectedSeatsFromServer && selectedSeatsFromServer.length > 0) {
                selectedSeatsFromServer.forEach(seatNumber => {
                    const checkbox = document.querySelector(`input[name="seat_numbers[]"][value="${seatNumber}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            updateBookingSummary();
            document.querySelectorAll('input[name="seat_numbers[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateBookingSummary);
            });
            <?php if (isset($errorMessage)): ?>
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            <?php endif; ?>
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