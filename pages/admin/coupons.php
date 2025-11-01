<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$pageTitle = 'İndirim Kuponları - Admin Paneli';
try {
    $possiblePaths = [
        __DIR__ . '/../../database/bilet-satis-veritabani.db',
        __DIR__ . '/../database/bilet-satis-veritabani.db',
        __DIR__ . 'database/bilet-satis-veritabani.db',
        'bilet-satis-veritabani.db'
    ];
    $dbPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $dbPath = $path;
            break;
        }
    }
    if (!$dbPath) {
        throw new Exception('Veritabanı dosyası bulunamadı');
    }
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('
        CREATE TABLE IF NOT EXISTS Coupons (
            id TEXT PRIMARY KEY,
            code TEXT NOT NULL,
            discount REAL NOT NULL,
            company_id TEXT,
            usage_limit INTEGER NOT NULL,
            expire_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
        )
    ');
    $coupons = $db->query('
        SELECT c.*, 
               bc.name as company_name,
               CASE 
                   WHEN c.company_id IS NULL THEN "Tüm Firmalar"
                   ELSE bc.name
               END as applicable_company
        FROM Coupons c
        LEFT JOIN Bus_Company bc ON c.company_id = bc.id
        ORDER BY c.created_at DESC
    ')->fetchAll();
    $companies = $db->query('
        SELECT id, name 
        FROM Bus_Company 
        ORDER BY name ASC
    ')->fetchAll();
} catch (Exception $e) {
    $coupons = [];
    $companies = [];
    $errorMessage = $e->getMessage();
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="admin-coupons-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="../admin_panel.php" class="text-white text-decoration-none">
                        <i class="fas fa-cogs me-1"></i>Admin Paneli
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-tags me-1"></i>İndirim Kuponları
                </li>
            </ol>
        </nav>
        <?php if (isset($errorMessage)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Veritabanı Bağlantı Hatası:</h6>
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    <p><strong>Kullanılan Yol:</strong> <?php echo isset($dbPath) ? $dbPath : 'Bulunamadı'; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-4" style="background: linear-gradient(45deg, #e83e8c, #dc3545); border-radius: 20px 20px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <div class="mb-2">
                                    <i class="fas fa-tags fa-3x text-white"></i>
                                </div>
                                <h2 class="text-white mb-0 fw-bold">İndirim Kuponları</h2>
                                <p class="text-white-50 mb-0 mt-2">Tüm firmalar için indirim kuponları oluşturun ve yönetin</p>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                                <i class="fas fa-plus me-1"></i>Yeni Kupon
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($coupons)): ?>
                <?php foreach ($coupons as $coupon): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card border-0 shadow-lg h-100" style="border-radius: 25px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.1)'">
                            <div class="card-header text-center py-4 position-relative" style="background: linear-gradient(135deg, #e83e8c 0%, #dc3545 100%); border-radius: 25px 25px 0 0; overflow: hidden;">
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);"></div>
                                <div class="position-relative">
                                    <div class="mb-3">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                                            <i class="fas fa-tag fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-white mb-2 fw-bold"><?php echo htmlspecialchars($coupon['code']); ?></h5>
                                    <div class="badge <?php echo (strtotime($coupon['expire_date']) > time()) ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2 rounded-pill" style="font-size: 0.9rem;">
                                        <i class="fas fa-<?php echo (strtotime($coupon['expire_date']) > time()) ? 'check-circle' : 'clock'; ?> me-1"></i>
                                        <?php echo (strtotime($coupon['expire_date']) > time()) ? 'Aktif' : 'Süresi Dolmuş'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <small class="text-muted"><?php echo htmlspecialchars($coupon['applicable_company']); ?></small>
                                </div>
                                <div class="coupon-details mb-3">
                                    <div class="text-center mb-2">
                                        <h4 class="fw-bold text-primary">
                                            %<?php echo number_format($coupon['discount'] ?? 0, 1); ?>
                                        </h4>
                                        <small class="text-muted">İndirim</small>
                                    </div>
                                    <div class="text-center mb-2">
                                        <small class="text-muted">
                                            Son Kullanma: <?php echo date('d.m.Y', strtotime($coupon['expire_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="coupon-actions">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-coupon-btn"
                                                data-coupon-id="<?php echo $coupon['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCouponModal">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-coupon-btn"
                                                data-coupon-id="<?php echo $coupon['id']; ?>"
                                                data-coupon-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteCouponModal">
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
                            <i class="fas fa-tags fa-5x mb-4"></i>
                            <h4>Henüz kupon yok</h4>
                            <p class="mb-3">Henüz indirim kuponu oluşturulmamış.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                                <i class="fas fa-plus me-1"></i>İlk Kuponu Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="editCouponModal" tabindex="-1" aria-labelledby="editCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCouponModalLabel">
                    <i class="fas fa-edit me-2"></i>Kupon Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCouponForm">
                <div class="modal-body">
                    <input type="hidden" id="editCouponId" name="coupon_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editCouponCode" class="form-label">Kupon Kodu</label>
                            <input type="text" class="form-control" id="editCouponCode" name="code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editCompanyId" class="form-label">Firma</label>
                            <select class="form-select" id="editCompanyId" name="company_id">
                                <option value="">Tüm Firmalar</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDiscount" class="form-label">İndirim Oranı (%)</label>
                            <input type="number" class="form-control" id="editDiscount" name="discount" step="0.01" min="0" max="100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editUsageLimit" class="form-label">Kullanım Limiti</label>
                            <input type="number" class="form-control" id="editUsageLimit" name="usage_limit" min="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editExpireDate" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="editExpireDate" name="expire_date" required>
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
<div class="modal fade" id="deleteCouponModal" tabindex="-1" aria-labelledby="deleteCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCouponModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Kupon Sil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCouponForm">
                <div class="modal-body">
                    <input type="hidden" id="deleteCouponId" name="coupon_id">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                        <h5>Bu işlem geri alınamaz!</h5>
                        <p class="mb-0">
                            <strong id="deleteCouponCode"></strong> kuponunu silmek istediğinizden emin misiniz?
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
<div class="modal fade" id="createCouponModal" tabindex="-1" aria-labelledby="createCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCouponModalLabel">
                    <i class="fas fa-plus me-2"></i>Yeni İndirim Kuponu Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCouponForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createCouponCode" class="form-label">Kupon Kodu</label>
                            <input type="text" class="form-control" id="createCouponCode" name="code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createCompanyId" class="form-label">Firma</label>
                            <select class="form-select" id="createCompanyId" name="company_id">
                                <option value="">Tüm Firmalar</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createDiscount" class="form-label">İndirim Oranı (%)</label>
                            <input type="number" class="form-control" id="createDiscount" name="discount" step="0.01" min="0" max="100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createUsageLimit" class="form-label">Kullanım Limiti</label>
                            <input type="number" class="form-control" id="createUsageLimit" name="usage_limit" min="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="createExpireDate" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="createExpireDate" name="expire_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Kupon Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    document.getElementById('createExpireDate').value = nextMonthStr;
    document.querySelectorAll('.edit-coupon-btn').forEach(button => {
        button.addEventListener('click', function() {
            const couponId = this.getAttribute('data-coupon-id');
            fetch('coupon_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get&coupon_id=' + encodeURIComponent(couponId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const coupon = data.coupon;
                    document.getElementById('editCouponId').value = coupon.id;
                    document.getElementById('editCouponCode').value = coupon.code;
                    document.getElementById('editCompanyId').value = coupon.company_id || '';
                    document.getElementById('editDiscount').value = coupon.discount;
                    document.getElementById('editUsageLimit').value = coupon.usage_limit;
                    document.getElementById('editExpireDate').value = coupon.expire_date;
                } else {
                    alert('Kupon bilgileri yüklenemedi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu.');
            });
        });
    });
    document.querySelectorAll('.delete-coupon-btn').forEach(button => {
        button.addEventListener('click', function() {
            const couponId = this.getAttribute('data-coupon-id');
            const couponCode = this.getAttribute('data-coupon-code');
            document.getElementById('deleteCouponId').value = couponId;
            document.getElementById('deleteCouponCode').textContent = couponCode;
        });
    });
    document.getElementById('createCouponForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');
        fetch('coupon_actions.php', {
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
    document.getElementById('editCouponForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');
        fetch('coupon_actions.php', {
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
    document.getElementById('deleteCouponForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'delete');
        fetch('coupon_actions.php', {
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