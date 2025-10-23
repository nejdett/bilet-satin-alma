<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('company');
$pageTitle = 'Sefer Yönetimi - Firma Paneli';
$currentUser = SessionManager::getCurrentUser();
$companyId = SessionManager::getCurrentCompanyId();
if (!$companyId) {
    $companyId = $currentUser['company_id'] ?? null;
    if (!$companyId) {
        SessionManager::setFlashMessage('Firma bilginiz bulunamadı. Lütfen yöneticiye başvurun.', 'error');
        header('Location: ../company_panel.php');
        exit;
    }
}
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare('SELECT * FROM Bus_Company WHERE id = ?');
    $stmt->execute([$companyId]);
    $companyInfo = $stmt->fetch();
    $currentDateTime = date('Y-m-d H:i:s');
    $tripsStmt = $db->prepare('
        SELECT t.*, 
               COUNT(tk.id) as sold_tickets,
               (COALESCE(t.capacity, 40) - COUNT(tk.id)) as remaining_seats
        FROM Trips t
        LEFT JOIN Tickets tk ON t.id = tk.trip_id
        WHERE t.company_id = ? AND t.departure_time > ?
        GROUP BY t.id
        ORDER BY t.departure_time ASC
    ');
    $tripsStmt->execute([$companyId, $currentDateTime]);
    $trips = $tripsStmt->fetchAll();
} catch (Exception $e) {
    $companyInfo = null;
    $trips = [];
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="company-trips-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="../company_panel.php" class="text-white text-decoration-none">
                        <i class="fas fa-building me-1"></i>Firma Paneli
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-bus me-1"></i>Sefer Yönetimi
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <div class="mb-2">
                                    <i class="fas fa-bus fa-3x text-white"></i>
                                </div>
                                <h2 class="text-white mb-0 fw-bold">Sefer Yönetimi</h2>
                                <p class="text-white-50 mb-0 mt-2">
                                    <?php echo $companyInfo ? htmlspecialchars($companyInfo['name']) : 'Firma'; ?> - Seferlerinizi yönetin
                                </p>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createTripModal">
                                <i class="fas fa-plus me-1"></i>Yeni Sefer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($trips)): ?>
                <?php foreach ($trips as $trip): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card shadow border-0 h-100" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <div class="trip-icon mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-bus fa-2x text-white"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($trip['departure_city']); ?> → <?php echo htmlspecialchars($trip['destination_city']); ?>
                                    </h5>
                                    <small class="text-muted">ID: <?php echo substr($trip['id'], 0, 8); ?>...</small>
                                </div>
                                <div class="trip-details mb-3">
                                    <div class="row text-center mb-2">
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-primary"><?php echo date('d.m.Y', strtotime($trip['departure_time'])); ?></h6>
                                                <small class="text-muted">Kalkış Tarihi</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-success"><?php echo date('H:i', strtotime($trip['departure_time'])); ?></h6>
                                                <small class="text-muted">Kalkış Saati</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center mb-2">
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-info"><?php echo date('d.m.Y', strtotime($trip['arrival_time'])); ?></h6>
                                                <small class="text-muted">Varış Tarihi</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-warning"><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></h6>
                                                <small class="text-muted">Varış Saati</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center mb-2">
                                        <div class="col-4">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-danger"><?php echo $trip['sold_tickets']; ?></h6>
                                                <small class="text-muted">Satılan</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-success"><?php echo $trip['remaining_seats']; ?></h6>
                                                <small class="text-muted">Kalan</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-secondary"><?php echo $trip['capacity'] ?? 40; ?></h6>
                                                <small class="text-muted">Toplam</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <span class="badge bg-success fs-6 px-3 py-2"><?php echo number_format($trip['price'], 2); ?> ₺</span>
                                    </div>
                                </div>
                                <div class="trip-actions">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-trip-btn"
                                                data-trip-id="<?php echo $trip['id']; ?>"
                                                data-departure-city="<?php echo htmlspecialchars($trip['departure_city']); ?>"
                                                data-destination-city="<?php echo htmlspecialchars($trip['destination_city']); ?>"
                                                data-departure-time="<?php echo $trip['departure_time']; ?>"
                                                data-arrival-time="<?php echo $trip['arrival_time']; ?>"
                                                data-price="<?php echo $trip['price']; ?>"
                                                data-capacity="<?php echo $trip['capacity'] ?? 40; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editTripModal">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm view-trip-btn"
                                                data-trip-id="<?php echo $trip['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewTripModal">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-trip-btn"
                                                data-trip-id="<?php echo $trip['id']; ?>"
                                                data-trip-route="<?php echo htmlspecialchars($trip['departure_city'] . ' → ' . $trip['destination_city']); ?>"
                                                data-has-tickets="<?php echo $trip['sold_tickets']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteTripModal">
                                            <i class="fas fa-trash me-1"></i>Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-5 text-center text-muted">
                            <i class="fas fa-bus fa-5x mb-4"></i>
                            <h4>Aktif sefer yok</h4>
                            <p class="mb-3">Gelecek tarihli sefer bulunmuyor. Yeni sefer ekleyebilirsiniz.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTripModal">
                                <i class="fas fa-plus me-1"></i>İlk Seferi Ekle
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="createTripModal" tabindex="-1" aria-labelledby="createTripModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTripModalLabel">
                    <i class="fas fa-plus me-2"></i>Yeni Sefer Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTripForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createDepartureCity" class="form-label">Kalkış Şehri</label>
                            <input type="text" class="form-control" id="createDepartureCity" name="departure_city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createDestinationCity" class="form-label">Varış Şehri</label>
                            <input type="text" class="form-control" id="createDestinationCity" name="destination_city" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createDepartureDate" class="form-label">Kalkış Tarihi</label>
                            <input type="date" class="form-control" id="createDepartureDate" name="departure_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createDepartureTime" class="form-label">Kalkış Saati</label>
                            <input type="time" class="form-control" id="createDepartureTime" name="departure_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createArrivalDate" class="form-label">Varış Tarihi</label>
                            <input type="date" class="form-control" id="createArrivalDate" name="arrival_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createArrivalTime" class="form-label">Varış Saati</label>
                            <input type="time" class="form-control" id="createArrivalTime" name="arrival_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createPrice" class="form-label">Bilet Fiyatı (₺)</label>
                            <input type="number" class="form-control" id="createPrice" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createCapacity" class="form-label">Koltuk Sayısı</label>
                            <input type="number" class="form-control" id="createCapacity" name="capacity" min="20" max="60" value="40" required>
                            <small class="form-text text-muted">20-60 arası koltuk sayısı seçebilirsiniz</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Sefer Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editTripModal" tabindex="-1" aria-labelledby="editTripModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTripModalLabel">
                    <i class="fas fa-edit me-2"></i>Sefer Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTripForm">
                <div class="modal-body">
                    <input type="hidden" id="editTripId" name="trip_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDepartureCity" class="form-label">Kalkış Şehri</label>
                            <input type="text" class="form-control" id="editDepartureCity" name="departure_city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editDestinationCity" class="form-label">Varış Şehri</label>
                            <input type="text" class="form-control" id="editDestinationCity" name="destination_city" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDepartureDate" class="form-label">Kalkış Tarihi</label>
                            <input type="date" class="form-control" id="editDepartureDate" name="departure_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editDepartureTime" class="form-label">Kalkış Saati</label>
                            <input type="time" class="form-control" id="editDepartureTime" name="departure_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editArrivalDate" class="form-label">Varış Tarihi</label>
                            <input type="date" class="form-control" id="editArrivalDate" name="arrival_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editArrivalTime" class="form-label">Varış Saati</label>
                            <input type="time" class="form-control" id="editArrivalTime" name="arrival_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editPrice" class="form-label">Bilet Fiyatı (₺)</label>
                            <input type="number" class="form-control" id="editPrice" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editCapacity" class="form-label">Koltuk Sayısı</label>
                            <input type="number" class="form-control" id="editCapacity" name="capacity" min="20" max="60" required>
                            <small class="form-text text-muted">20-60 arası koltuk sayısı seçebilirsiniz</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="viewTripModal" tabindex="-1" aria-labelledby="viewTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTripModalLabel">
                    <i class="fas fa-eye me-2"></i>Sefer Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="tripDetailsContent">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteTripModal" tabindex="-1" aria-labelledby="deleteTripModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteTripModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Sefer Sil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteTripForm">
                <div class="modal-body">
                    <input type="hidden" id="deleteTripId" name="trip_id">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                        <h5>Bu işlem geri alınamaz!</h5>
                        <p class="mb-0">
                            <strong id="deleteTripRoute"></strong> seferini silmek istediğinizden emin misiniz?
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('createDepartureDate').min = today;
    document.getElementById('editDepartureDate').min = today;
    document.getElementById('createArrivalDate').min = today;
    document.getElementById('editArrivalDate').min = today;
    document.getElementById('createDepartureDate').addEventListener('change', function() {
        document.getElementById('createArrivalDate').min = this.value;
    });
    document.getElementById('editDepartureDate').addEventListener('change', function() {
        document.getElementById('editArrivalDate').min = this.value;
    });
    document.getElementById('createTripForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const departureCity = document.getElementById('createDepartureCity').value.trim();
        const destinationCity = document.getElementById('createDestinationCity').value.trim();
        if (departureCity && destinationCity) {
            const normalizeTurkish = (text) => {
                return text.toLowerCase()
                          .replace(/İ/g, 'i')
                          .replace(/I/g, 'ı')
                          .replace(/Ğ/g, 'ğ')
                          .replace(/Ü/g, 'ü')
                          .replace(/Ş/g, 'ş')
                          .replace(/Ö/g, 'ö')
                          .replace(/Ç/g, 'ç');
            };
            if (normalizeTurkish(departureCity) === normalizeTurkish(destinationCity)) {
                alert('Kalkış ve varış şehri aynı olamaz.');
                return;
            }
        }
        const formData = new FormData(this);
        formData.append('action', 'create');
        fetch('trip_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    });
    document.querySelectorAll('.edit-trip-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tripId = this.getAttribute('data-trip-id');
            const departureCity = this.getAttribute('data-departure-city');
            const destinationCity = this.getAttribute('data-destination-city');
            const departureTime = this.getAttribute('data-departure-time');
            const arrivalTime = this.getAttribute('data-arrival-time');
            const price = this.getAttribute('data-price');
            const capacity = this.getAttribute('data-capacity');
            document.getElementById('editTripId').value = tripId;
            document.getElementById('editDepartureCity').value = departureCity;
            document.getElementById('editDestinationCity').value = destinationCity;
            const departureDateTime = new Date(departureTime);
            document.getElementById('editDepartureDate').value = departureDateTime.toISOString().split('T')[0];
            document.getElementById('editDepartureTime').value = departureDateTime.toTimeString().split(' ')[0].substring(0, 5);
            const arrivalDateTime = new Date(arrivalTime);
            document.getElementById('editArrivalDate').value = arrivalDateTime.toISOString().split('T')[0];
            document.getElementById('editArrivalTime').value = arrivalDateTime.toTimeString().split(' ')[0].substring(0, 5);
            document.getElementById('editArrivalDate').min = document.getElementById('editDepartureDate').value;
            document.getElementById('editPrice').value = price;
            document.getElementById('editCapacity').value = capacity;
        });
    });
    document.getElementById('editTripForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const departureCity = document.getElementById('editDepartureCity').value.trim();
        const destinationCity = document.getElementById('editDestinationCity').value.trim();
        if (departureCity && destinationCity) {
            const normalizeTurkish = (text) => {
                return text.toLowerCase()
                          .replace(/İ/g, 'i')
                          .replace(/I/g, 'ı')
                          .replace(/Ğ/g, 'ğ')
                          .replace(/Ü/g, 'ü')
                          .replace(/Ş/g, 'ş')
                          .replace(/Ö/g, 'ö')
                          .replace(/Ç/g, 'ç');
            };
            if (normalizeTurkish(departureCity) === normalizeTurkish(destinationCity)) {
                alert('Kalkış ve varış şehri aynı olamaz.');
                return;
            }
        }
        const formData = new FormData(this);
        formData.append('action', 'update');
        fetch('trip_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    });
    document.querySelectorAll('.view-trip-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tripId = this.getAttribute('data-trip-id');
            document.getElementById('tripDetailsContent').innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>';
            fetch('trip_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get&trip_id=' + encodeURIComponent(tripId)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('tripDetailsContent').innerHTML = data.html;
                    } else {
                        document.getElementById('tripDetailsContent').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + (data.message || 'Sefer detayları yüklenemedi.') + '</div>';
                        console.error('Error message:', data.message);
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    document.getElementById('tripDetailsContent').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Veri formatı hatası.</div>';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                document.getElementById('tripDetailsContent').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Bağlantı hatası: ' + error.message + '</div>';
            });
        });
    });
    document.querySelectorAll('.delete-trip-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tripId = this.getAttribute('data-trip-id');
            const tripRoute = this.getAttribute('data-trip-route');
            const hasTickets = parseInt(this.getAttribute('data-has-tickets'));
            document.getElementById('deleteTripId').value = tripId;
            document.getElementById('deleteTripRoute').textContent = tripRoute;
            if (hasTickets > 0) {
                const warningMsg = document.createElement('div');
                warningMsg.className = 'alert alert-warning mt-3';
                warningMsg.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Bu sefere ' + hasTickets + ' bilet satılmıştır. Silme işlemi tüm biletleri iptal edecektir.';
                document.querySelector('#deleteTripModal .modal-body').appendChild(warningMsg);
            }
        });
    });
    document.getElementById('deleteTripForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'delete');
        fetch('trip_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    });
});
</script>
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