<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../includes/path_helpers.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
$pageTitle = 'Kullanıcı Yönetimi - Admin Paneli';
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../bilet-satis-veritabani.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $users = $db->query('
        SELECT u.id, u.full_name, u.email, u.role, u.balance, u.created_at, u.last_login, u.company_id,
               bc.name as company_name
        FROM User u
        LEFT JOIN Bus_Company bc ON u.company_id = bc.id
        ORDER BY u.created_at DESC
    ')->fetchAll();
    $companies = $db->query('
        SELECT id, name 
        FROM Bus_Company 
        ORDER BY name ASC
    ')->fetchAll();
} catch (Exception $e) {
    $users = [];
    $companies = [];
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="admin-users-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-transparent">
                <li class="breadcrumb-item">
                    <a href="../admin_panel.php" class="text-white text-decoration-none">
                        <i class="fas fa-cogs me-1"></i>Admin Paneli
                    </a>
                </li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">
                    <i class="fas fa-users me-1"></i>Kullanıcı Yönetimi
                </li>
            </ol>
        </nav>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 20px 20px 0 0;">
                        <div class="mb-2">
                            <i class="fas fa-users fa-3x text-white"></i>
                        </div>
                        <h2 class="text-white mb-0 fw-bold">Kullanıcı Yönetimi</h2>
                        <p class="text-white-50 mb-0 mt-2">Sistem kullanıcılarını yönetin</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header py-3" style="background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 20px 20px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="text-white mb-0 fw-bold">
                                <i class="fas fa-list me-2"></i>Tüm Kullanıcılar (<?php echo count($users); ?>)
                            </h5>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-plus me-1"></i>Yeni Kullanıcı
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($users)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th class="px-4 py-3">Kullanıcı</th>
                                            <th class="px-4 py-3">E-posta</th>
                                            <th class="px-4 py-3">Rol</th>
                                            <th class="px-4 py-3">Bakiye</th>
                                            <th class="px-4 py-3">Firma</th>
                                            <th class="px-4 py-3">Kayıt Tarihi</th>
                                            <th class="px-4 py-3">Son Giriş</th>
                                            <th class="px-4 py-3">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-3" style="width: 40px; height: 40px; background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                            <small class="text-muted">ID: <?php echo substr($user['id'], 0, 8); ?>...</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($user['email']); ?></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'company' ? 'bg-warning' : 'bg-primary'); ?> px-3 py-2">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-success px-3 py-2 me-2">
                                                            <?php echo number_format($user['balance'] ?? 0, 2); ?> ₺
                                                        </span>
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success manage-balance-btn" 
                                                                    title="Bakiye Yönet"
                                                                    data-user-id="<?php echo $user['id']; ?>"
                                                                    data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                    data-user-balance="<?php echo $user['balance'] ?? 0; ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#manageBalanceModal">
                                                                <i class="fas fa-wallet"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php if ($user['company_name']): ?>
                                                        <span class="fw-semibold text-success">
                                                            <i class="fas fa-building me-1"></i>
                                                            <?php echo htmlspecialchars($user['company_name']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-muted"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-muted">
                                                        <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Hiç giriş yapmamış'; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" 
                                                                title="Düzenle"
                                                                data-user-id="<?php echo $user['id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                data-user-role="<?php echo $user['role']; ?>"
                                                                data-user-company="<?php echo $user['company_id'] ?? ''; ?>"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editUserModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" 
                                                                    title="Sil"
                                                                    data-user-id="<?php echo $user['id']; ?>"
                                                                    data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteUserModal">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-users fa-5x mb-4"></i>
                                <h4>Henüz kullanıcı yok</h4>
                                <p class="mb-0">Sistem henüz kullanıcı kaydı içermiyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Yeni Kullanıcı Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_full_name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">E-posta <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="create_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="create_role" name="role" required onchange="toggleCompanyField('create')">
                            <option value="">Rol seçin</option>
                            <option value="user">Normal Kullanıcı</option>
                            <option value="company">Firma Kullanıcısı</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="create_company_field" style="display: none;">
                        <label for="create_company_id" class="form-label">Firma <span class="text-danger">*</span></label>
                        <select class="form-select" id="create_company_id" name="company_id">
                            <option value="">Firma seçin</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="create_password" class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="create_password" name="password" required minlength="8">
                        <div class="form-text">En az 8 karakter olmalıdır.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Kullanıcı Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Kullanıcı Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">E-posta <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_role" name="role" required onchange="toggleCompanyField('edit')">
                            <option value="user">Normal Kullanıcı</option>
                            <option value="company">Firma Kullanıcısı</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_company_field" style="display: none;">
                        <label for="edit_company_id" class="form-label">Firma <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_company_id" name="company_id">
                            <option value="">Firma seçin</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="8">
                        <div class="form-text">Boş bırakırsanız şifre değişmez.</div>
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
<div class="modal fade" id="manageBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(45deg, #28a745, #20c997);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-wallet me-2"></i>Bakiye Yönet
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="manageBalanceForm">
                <div class="modal-body">
                    <input type="hidden" id="balanceUserId" name="user_id">
                    <div class="mb-4 text-center">
                        <h6 class="text-muted mb-2">Kullanıcı</h6>
                        <h5 class="fw-bold" id="balanceUserName"></h5>
                    </div>
                    <div class="mb-4 text-center">
                        <div class="p-3 rounded-3" style="background: linear-gradient(135deg, #e8f5e8, #c8e6c9);">
                            <h6 class="text-success mb-2">Mevcut Bakiye</h6>
                            <h3 class="fw-bold text-success mb-0">
                                <span id="currentBalance">0.00</span> ₺
                            </h3>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="balanceAction" class="form-label">İşlem Türü</label>
                        <select class="form-select" id="balanceAction" name="action" required>
                            <option value="add">Bakiye Ekle (+)</option>
                            <option value="subtract">Bakiye Düş (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="balanceAmount" class="form-label">Miktar (₺)</label>
                        <input type="number" class="form-control" id="balanceAmount" name="amount" 
                               step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="balanceNote" class="form-label">Not (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="balanceNote" name="note" rows="2" 
                                  placeholder="İşlem ile ilgili not..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Uygula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i>Kullanıcı Sil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteUserForm">
                <input type="hidden" id="delete_user_id" name="user_id">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat!</strong> Bu işlem geri alınamaz.
                    </div>
                    <p>
                        <strong id="delete_user_name"></strong> kullanıcısını silmek istediğinizden emin misiniz?
                    </p>
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
function toggleCompanyField(prefix) {
    const roleSelect = document.getElementById(prefix + '_role');
    const companyField = document.getElementById(prefix + '_company_field');
    const companySelect = document.getElementById(prefix + '_company_id');
    if (roleSelect.value === 'company') {
        companyField.style.display = 'block';
        companySelect.required = true;
    } else {
        companyField.style.display = 'none';
        companySelect.required = false;
        companySelect.value = '';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.manage-balance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('balanceUserId').value = this.dataset.userId;
            document.getElementById('balanceUserName').textContent = this.dataset.userName;
            document.getElementById('currentBalance').textContent = parseFloat(this.dataset.userBalance).toFixed(2);
            document.getElementById('balanceAmount').value = '';
            document.getElementById('balanceNote').value = '';
            document.getElementById('balanceAction').value = 'add';
        });
    });
    document.getElementById('manageBalanceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('user_actions.php', {
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
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_user_id').value = this.dataset.userId;
            document.getElementById('edit_full_name').value = this.dataset.userName;
            document.getElementById('edit_email').value = this.dataset.userEmail;
            document.getElementById('edit_role').value = this.dataset.userRole;
            document.getElementById('edit_company_id').value = this.dataset.userCompany || '';
            document.getElementById('edit_password').value = '';
            toggleCompanyField('edit');
        });
    });
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_user_id').value = this.dataset.userId;
            document.getElementById('delete_user_name').textContent = this.dataset.userName;
        });
    });
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('user_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Kullanıcı başarıyla oluşturuldu!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bir hata oluştu: ' + error.message);
        });
    });
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');
        fetch('user_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Kullanıcı başarıyla güncellendi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bir hata oluştu: ' + error.message);
        });
    });
    document.getElementById('deleteUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'delete');
        fetch('user_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Kullanıcı başarıyla silindi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bir hata oluştu: ' + error.message);
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