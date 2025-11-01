<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('company');
$pageTitle = 'Kupon Yönetimi - Firma Paneli';
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
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare('SELECT * FROM Bus_Company WHERE id = ?');
    $stmt->execute([$companyId]);
    $companyInfo = $stmt->fetch();
    $couponsStmt = $db->prepare('
        SELECT * FROM Coupons 
        WHERE company_id = ?
        ORDER BY created_at DESC
    ');
    $couponsStmt->execute([$companyId]);
    $coupons = $couponsStmt->fetchAll();
    foreach ($coupons as &$coupon) {
        $coupon['usage_count'] = 0;
    }
} catch (Exception $e) {
    $companyInfo = null;
    $coupons = [];
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="company-coupons-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="../company_panel.php" class="text-white text-decoration-none">
                        <i class="fas fa-building me-1"></i>Firma Paneli
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-tags me-1"></i>Kupon Yönetimi
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-4" style="background: linear-gradient(45deg, #dc3545, #fd7e14); border-radius: 20px 20px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <div class="mb-2">
                                    <i class="fas fa-tags fa-3x text-white"></i>
                                </div>
                                <h2 class="text-white mb-0 fw-bold">Kupon Yönetimi</h2>
                                <p class="text-white-50 mb-0 mt-2">
                                    <?php echo $companyInfo ? htmlspecialchars($companyInfo['name']) : 'Firma'; ?> - İndirim kuponlarınızı yönetin
                                </p>
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
                        <div class="card shadow border-0 h-100" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <div class="coupon-icon mx-auto mb-3" style="width: 60px; height: 60px; background: linear-gradient(45deg, #dc3545, #fd7e14); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tags fa-2x text-white"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($coupon['code']); ?>
                                    </h5>
                                    <small class="text-muted">Kupon Kodu</small>
                                </div>
                                <div class="coupon-details mb-3">
                                    <div class="row text-center mb-2">
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-danger">%<?php echo $coupon['discount']; ?></h6>
                                                <small class="text-muted">İndirim</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="detail-item">
                                                <h6 class="mb-0 fw-bold text-info"><?php echo $coupon['usage_count']; ?></h6>
                                                <small class="text-muted">Kullanım</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($coupon['expire_date']): ?>
                                    <div class="text-center mb-2">
                                        <small class="text-muted">Son Kullanma: <?php echo date('d.m.Y', strtotime($coupon['expire_date'])); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($coupon['usage_limit']): ?>
                                    <div class="text-center mb-2">
                                        <small class="text-muted">Kullanım Limiti: <?php echo $coupon['usage_limit']; ?></small>
                                    </div>
                                    <?php endif; ?>
                                    <div class="text-center">
                                        <span class="badge bg-success fs-6 px-3 py-2">Aktif</span>
                                    </div>
                                </div>
                                <div class="coupon-actions">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-coupon-btn"
                                                data-coupon-id="<?php echo $coupon['id']; ?>"
                                                data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                                data-discount-value="<?php echo $coupon['discount']; ?>"
                                                data-expiry-date="<?php echo $coupon['expire_date']; ?>"
                                                data-usage-limit="<?php echo $coupon['usage_limit']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCouponModal">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
                                        <?php if ($coupon['usage_count'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm delete-coupon-btn"
                                                    data-coupon-id="<?php echo $coupon['id']; ?>"
                                                    data-coupon-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteCouponModal">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Kullanıldığı için silinemez">
                                                <i class="fas fa-lock me-1"></i>Kilitli
                                            </button>
                                        <?php endif; ?>
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
                            <p class="mb-3">Firmanız için henüz indirim kuponu oluşturulmamış.</p>
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
<div class="modal fade" id="createCouponModal" tabindex="-1" aria-labelledby="createCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCouponModalLabel">
                    <i class="fas fa-plus me-2"></i>Yeni Kupon Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCouponForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createCouponCode" class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" id="createCouponCode" name="code" required maxlength="20">
                        <small class="form-text text-muted">Benzersiz kupon kodu (örn: INDIRIM20)</small>
                    </div>
                    <div class="mb-3">
                        <label for="createDiscountValue" class="form-label">İndirim Yüzdesi (%)</label>
                        <input type="number" class="form-control" id="createDiscountValue" name="discount_value" step="1" min="1" max="100" required>
                        <small class="form-text text-muted">1-100 arası yüzde değeri girin</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createExpiryDate" class="form-label">Son Kullanma Tarihi (İsteğe Bağlı)</label>
                            <input type="date" class="form-control" id="createExpiryDate" name="expiry_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createUsageLimit" class="form-label">Kullanım Limiti (İsteğe Bağlı)</label>
                            <input type="number" class="form-control" id="createUsageLimit" name="usage_limit" min="1">
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
<div class="modal fade" id="editCouponModal" tabindex="-1" aria-labelledby="editCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    <div class="mb-3">
                        <label for="editCouponCode" class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" id="editCouponCode" name="code" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label for="editDiscountValue" class="form-label">İndirim Yüzdesi (%)</label>
                        <input type="number" class="form-control" id="editDiscountValue" name="discount_value" step="1" min="1" max="100" required>
                        <small class="form-text text-muted">1-100 arası yüzde değeri girin</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editExpiryDate" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="editExpiryDate" name="expiry_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editUsageLimit" class="form-label">Kullanım Limiti</label>
                            <input type="number" class="form-control" id="editUsageLimit" name="usage_limit" min="1">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('createExpiryDate').min = today;
    document.getElementById('editExpiryDate').min = today;
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
    document.querySelectorAll('.edit-coupon-btn').forEach(button => {
        button.addEventListener('click', function() {
            const couponId = this.getAttribute('data-coupon-id');
            const code = this.getAttribute('data-code');
            const discountValue = this.getAttribute('data-discount-value');
            const expiryDate = this.getAttribute('data-expiry-date');
            const usageLimit = this.getAttribute('data-usage-limit');
            document.getElementById('editCouponId').value = couponId;
            document.getElementById('editCouponCode').value = code;
            document.getElementById('editDiscountValue').value = discountValue;
            document.getElementById('editExpiryDate').value = expiryDate || '';
            document.getElementById('editUsageLimit').value = usageLimit || '';
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
    document.querySelectorAll('.delete-coupon-btn').forEach(button => {
        button.addEventListener('click', function() {
            const couponId = this.getAttribute('data-coupon-id');
            const couponCode = this.getAttribute('data-coupon-code');
            document.getElementById('deleteCouponId').value = couponId;
            document.getElementById('deleteCouponCode').textContent = couponCode;
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