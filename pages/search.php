<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Trip.php';
require_once __DIR__ . '/../includes/path_helpers.php';

SessionManager::startSession();

$trip = new Trip();

// Get search parameters from URL
$departure = $_GET['departure'] ?? '';
$arrival = $_GET['arrival'] ?? '';
$date = $_GET['date'] ?? '';

$searchErrors = [];
if (empty($departure)) {
    $searchErrors[] = 'Kalkış yeri seçiniz.';
}
if (empty($arrival)) {
    $searchErrors[] = 'Varış yeri seçiniz.';
}
if (!empty($departure) && !empty($arrival)) {
    $normalizedDeparture = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $departure), 'UTF-8');
    $normalizedArrival = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $arrival), 'UTF-8');
    if ($normalizedDeparture === $normalizedArrival) {
        $searchErrors[] = 'Kalkış ve varış yeri aynı olamaz.';
    }
}
$trips = [];
if (empty($searchErrors)) {
    $trips = $trip->searchTrips($departure, $arrival, $date);
}
$pageTitle = 'Sefer Arama Sonuçları - Otobüs Bileti Satış Platformu';
function formatTurkishDate($date, $includeDay = false) {
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    $days = [
        'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
    ];
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $formatted = $day . ' ' . $month . ' ' . $year;
    if ($includeDay) {
        $dayName = $days[date('l', $timestamp)];
        $formatted .= ', ' . $dayName;
    }
    return $formatted;
}
include __DIR__ . '/../includes/header.php';
?>
<div class="search-page-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="<?php echo getAbsoluteUrl('index.php'); ?>" class="text-white text-decoration-none">
                        <i class="fas fa-home me-1"></i>Ana Sayfa
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-search me-1"></i>Sefer Arama
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="me-3 mb-2">
                                    <span class="badge bg-success px-3 py-2 rounded-pill">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($departure); ?>
                                    </span>
                                </div>
                                <div class="me-3 mb-2">
                                    <i class="fas fa-arrow-right text-primary fa-lg"></i>
                                </div>
                                <div class="me-3 mb-2">
                                    <span class="badge bg-danger px-3 py-2 rounded-pill">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($arrival); ?>
                                    </span>
                                </div>
                                <div class="me-3 mb-2">
                                    <span class="badge bg-primary px-3 py-2 rounded-pill">
                                        <i class="fas fa-calendar me-1"></i><?php echo formatTurkishDate($date, true); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-info px-3 py-2 rounded-pill">
                                    <i class="fas fa-bus me-1"></i><?php echo count($trips); ?> sefer bulundu
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($searchErrors)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px; background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-4">
                            <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                                    <div>
                                        <h5 class="alert-heading mb-2">Arama Hatası</h5>
                                        <ul class="mb-0">
                                            <?php foreach ($searchErrors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (empty($trips)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="empty-state-card card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body text-center p-5">
                            <div class="empty-state-icon mb-4">
                                <i class="fas fa-search fa-5x text-primary" style="opacity: 0.7;"></i>
                            </div>
                            <h2 class="text-dark mb-3 fw-bold">Sefer Bulunamadı</h2>
                            <p class="text-muted mb-4 lead">
                                Bu güzergahta ve tarihte sefer bulunmuyor.
                            </p>
                            <a href="<?php echo getAbsoluteUrl('index.php'); ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Yeni Arama Yap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                        <div class="card-header text-center py-3" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 20px 20px 0 0; border-bottom: none; margin-bottom: 0;">
                            <h5 class="text-white mb-0 fw-bold">
                                <i class="fas fa-list me-2"></i>Bulunan Seferler
                            </h5>
                        </div>
                        <div class="card-body p-0" style="margin-top: 0; padding-top: 0;">
                            <div class="trip-results-container">
                                <?php foreach ($trips as $index => $tripData): ?>
                                    <?php if ($index > 0): ?>
                                        <hr class="trip-divider my-0" style="border-color: rgba(0, 0, 0, 0.1); border-width: 1px; margin: 0;">
                                    <?php endif; ?>
                                    <div class="trip-item" style="padding: 1.5rem; transition: all 0.3s ease;">
                                        <div class="row align-items-center">
                                            <div class="col-lg-2 col-md-3 col-6 mb-3 mb-lg-0">
                                                <div class="trip-time-info text-center">
                                                    <div class="time-display">
                                                        <h4 class="mb-1 fw-bold text-primary">
                                                            <?php echo $trip->formatTripTime($tripData['departure_time'] ?? '', 'H:i'); ?>
                                                        </h4>
                                                        <p class="mb-0 text-muted small fw-semibold">
                                                            <i class="fas fa-map-marker-alt text-success me-1"></i>
                                                            <?php echo htmlspecialchars($tripData['departure_city'] ?? ''); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-3 col-6 mb-3 mb-lg-0">
                                                <div class="journey-duration text-center">
                                                    <div class="duration-line position-relative">
                                                        <div class="duration-bar"></div>
                                                        <div class="duration-icon">
                                                            <i class="fas fa-bus text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0 text-muted small mt-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $trip->calculateDuration($tripData['departure_time'] ?? '', $tripData['arrival_time'] ?? ''); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-3 col-6 mb-3 mb-lg-0">
                                                <div class="trip-time-info text-center">
                                                    <div class="time-display">
                                                        <h4 class="mb-1 fw-bold text-danger">
                                                            <?php echo $trip->formatTripTime($tripData['arrival_time'] ?? '', 'H:i'); ?>
                                                        </h4>
                                                        <p class="mb-0 text-muted small fw-semibold">
                                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                            <?php echo htmlspecialchars($tripData['destination_city'] ?? ''); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-3 col-6 mb-3 mb-lg-0">
                                                <div class="company-info text-center">
                                                    <div class="company-badge">
                                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                                            <i class="fas fa-building me-1"></i>
                                                            <?php echo htmlspecialchars($tripData['company_name'] ?? 'Firma'); ?>
                                                        </span>
                                                    </div>
                                                    <p class="mb-0 text-muted small mt-2">
                                                        <i class="fas fa-users me-1"></i>
                                                        <?php echo ($tripData['available_seats'] ?? 0); ?> koltuk
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-12">
                                                <div class="price-action-section">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                                                            <div class="price-display">
                                                                <h3 class="mb-0 fw-bold text-success">
                                                                    <?php echo number_format($tripData['price'] ?? 0, 2); ?> ₺
                                                                </h3>
                                                                <small class="text-muted">Kişi başı</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 text-center">
                                                            <?php if (SessionManager::isLoggedIn()): ?>
                                                                <a href="booking.php?trip_id=<?php echo urlencode($tripData['id'] ?? ''); ?>" 
                                                                   class="btn btn-primary btn-lg fw-semibold w-100" 
                                                                   style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                                                    <i class="fas fa-ticket-alt me-2"></i>
                                                                    Bilet Al
                                                                </a>
                                                            <?php else: ?>
                                                                <button type="button" 
                                                                        class="btn btn-primary btn-lg fw-semibold w-100" 
                                                                        style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#loginModal">
                                                                    <i class="fas fa-ticket-alt me-2"></i>
                                                                    Bilet Al
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if (!SessionManager::isLoggedIn()): ?>
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Giriş Gerekli</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bilet satın almak için sisteme giriş yapmanız gerekmektedir.</p>
                <p class="mb-0">Hesabınız yoksa hemen kayıt olabilirsiniz.</p>
            </div>
            <div class="modal-footer">
                <a href="login.php" class="btn btn-primary">Giriş Yap</a>
                <a href="register.php" class="btn btn-outline-primary">Kayıt Ol</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<style>
body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    min-height: 100vh;
}
.search-page-wrapper {
    min-height: 100vh;
}
.trip-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.duration-bar {
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, #28a745, #007bff);
    border-radius: 1px;
}
.duration-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 5px;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
}
.btn {
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
@media (max-width: 768px) {
    .search-page-wrapper {
        padding-top: 100px;
        padding-bottom: 30px;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .trip-item {
        padding: 1rem !important;
    }
    .price-display h3 {
        font-size: 1.5rem;
    }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});
</script>
</body>
</html>