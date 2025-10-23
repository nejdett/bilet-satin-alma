<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$pageTitle = 'Firma Yönetimi - Admin Paneli';
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $companies = $db->query('
        SELECT bc.*, 
               COUNT(DISTINCT t.id) as trip_count,
               COUNT(DISTINCT u.id) as user_count
        FROM Bus_Company bc
        LEFT JOIN Trips t ON bc.id = t.company_id
        LEFT JOIN User u ON bc.id = u.company_id
        GROUP BY bc.id
        ORDER BY bc.created_at DESC
    ')->fetchAll();
} catch (Exception $e) {
    $companies = [];
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="admin-companies-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="../admin_panel.php" class="text-white text-decoration-none">
                        <i class="fas fa-cogs me-1"></i>Admin Paneli
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-building me-1"></i>Firma Yönetimi
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-4" style="background: linear-gradient(45deg, #ffc107, #e0a800); border-radius: 20px 20px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <div class="mb-2">
                                    <i class="fas fa-building fa-3x text-white"></i>
                                </div>
                                <h2 class="text-white mb-0 fw-bold">Firma Yönetimi</h2>
                                <p class="text-white-50 mb-0 mt-2">Otobüs firmalarını yönetin</p>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                                <i class="fas fa-plus me-1"></i>Yeni Firma
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($companies)): ?>
                <?php foreach ($companies as $company): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100" style="border-radius: 25px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.1)'">
                            <div class="card-header text-center py-4 position-relative" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); border-radius: 25px 25px 0 0; overflow: hidden;">
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);"></div>
                                <div class="position-relative">
                                    <div class="mb-3">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                                            <i class="fas fa-building fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-white mb-2 fw-bold"><?php echo htmlspecialchars($company['name']); ?></h5>
                                    <small class="text-white-50">ID: <?php echo substr($company['id'], 0, 8); ?>...</small>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="company-stats mb-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <h6 class="mb-0 fw-bold text-primary"><?php echo $company['trip_count']; ?></h6>
                                                <small class="text-muted">Sefer</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <h6 class="mb-0 fw-bold text-success"><?php echo $company['user_count']; ?></h6>
                                                <small class="text-muted">Kullanıcı</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="company-info mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Kayıt: <?php echo date('d.m.Y', strtotime($company['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="company-actions">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-company-btn"
                                                data-company-id="<?php echo $company['id']; ?>"
                                                data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCompanyModal">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm view-company-btn"
                                                data-company-id="<?php echo $company['id']; ?>"
                                                data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                data-company-trips="<?php echo $company['trip_count']; ?>"
                                                data-company-users="<?php echo $company['user_count']; ?>"
                                                data-company-created="<?php echo date('d.m.Y', strtotime($company['created_at'])); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewCompanyModal">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-company-btn"
                                                data-company-id="<?php echo $company['id']; ?>"
                                                data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteCompanyModal">
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
                            <i class="fas fa-building fa-5x mb-4"></i>
                            <h4>Henüz firma yok</h4>
                            <p class="mb-0">Sistem henüz otobüs firması kaydı içermiyor.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="createCompanyModal" tabindex="-1" aria-labelledby="createCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCompanyModalLabel">
                    <i class="fas fa-plus me-2"></i>Yeni Firma Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCompanyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createCompanyName" class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" id="createCompanyName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCompanyModalLabel">
                    <i class="fas fa-edit me-2"></i>Firma Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCompanyForm">
                <div class="modal-body">
                    <input type="hidden" id="editCompanyId" name="company_id">
                    <div class="mb-3">
                        <label for="editCompanyName" class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" id="editCompanyName" name="name" required>
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
<div class="modal fade" id="viewCompanyModal" tabindex="-1" aria-labelledby="viewCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCompanyModalLabel">
                    <i class="fas fa-eye me-2"></i>Firma Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <div class="company-avatar mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(45deg, #ffc107, #e0a800); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-building fa-3x text-white"></i>
                                </div>
                                <h4 id="viewCompanyName" class="fw-bold mb-2"></h4>
                                <small class="text-muted">Firma ID: <span id="viewCompanyId"></span></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">İstatistikler</h6>
                                <div class="row">
                                    <div class="col-6 text-center">
                                        <div class="stat-item mb-3">
                                            <h4 id="viewCompanyTrips" class="mb-0 fw-bold text-primary"></h4>
                                            <small class="text-muted">Toplam Sefer</small>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="stat-item mb-3">
                                            <h4 id="viewCompanyUsers" class="mb-0 fw-bold text-success"></h4>
                                            <small class="text-muted">Kullanıcı Sayısı</small>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    <small class="text-muted">
                                        Kayıt Tarihi: <span id="viewCompanyCreated"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-labelledby="deleteCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCompanyModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Firma Sil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCompanyForm">
                <div class="modal-body">
                    <input type="hidden" id="deleteCompanyId" name="company_id">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                        <h5>Bu işlem geri alınamaz!</h5>
                        <p class="mb-0">
                            <strong id="deleteCompanyName"></strong> firmasını silmek istediğinizden emin misiniz?
                        </p>
                        <small class="text-muted mt-2 d-block">
                            Not: Firmaya ait seferler veya kullanıcılar varsa silme işlemi gerçekleştirilemez.
                        </small>
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
    document.getElementById('createCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');
        fetch('<?php echo getAbsoluteUrl('pages/admin/company_actions.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                let errorMsg = 'Hata: ' + data.message;
                if (data.debug) {
                    console.error('Debug info:', data.debug);
                    errorMsg += '\nDetay: ' + JSON.stringify(data.debug);
                }
                alert(errorMsg);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    });
    document.querySelectorAll('.edit-company-btn').forEach(button => {
        button.addEventListener('click', function() {
            const companyId = this.getAttribute('data-company-id');
            const companyName = this.getAttribute('data-company-name');
            document.getElementById('editCompanyId').value = companyId;
            document.getElementById('editCompanyName').value = companyName;
        });
    });
    document.getElementById('editCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');
        fetch('<?php echo getAbsoluteUrl('pages/admin/company_actions.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                let errorMsg = 'Hata: ' + data.message;
                if (data.debug) {
                    console.error('Debug info:', data.debug);
                    errorMsg += '\nDetay: ' + JSON.stringify(data.debug);
                }
                alert(errorMsg);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    });
    document.querySelectorAll('.view-company-btn').forEach(button => {
        button.addEventListener('click', function() {
            const companyId = this.getAttribute('data-company-id');
            const companyName = this.getAttribute('data-company-name');
            const companyTrips = this.getAttribute('data-company-trips');
            const companyUsers = this.getAttribute('data-company-users');
            const companyCreated = this.getAttribute('data-company-created');
            document.getElementById('viewCompanyName').textContent = companyName;
            document.getElementById('viewCompanyId').textContent = companyId.substring(0, 8) + '...';
            document.getElementById('viewCompanyTrips').textContent = companyTrips;
            document.getElementById('viewCompanyUsers').textContent = companyUsers;
            document.getElementById('viewCompanyCreated').textContent = companyCreated;
        });
    });
    document.querySelectorAll('.delete-company-btn').forEach(button => {
        button.addEventListener('click', function() {
            const companyId = this.getAttribute('data-company-id');
            const companyName = this.getAttribute('data-company-name');
            document.getElementById('deleteCompanyId').value = companyId;
            document.getElementById('deleteCompanyName').textContent = companyName;
        });
    });
    document.getElementById('deleteCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'delete');
        fetch('<?php echo getAbsoluteUrl('pages/admin/company_actions.php'); ?>', {
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