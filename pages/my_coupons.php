<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Coupon.php';
SessionManager::startSession();
SessionManager::requireLogin();
$pageTitle = 'Kuponlarım - Otobüs Bileti Satış Platformu';
$user = new User();
$coupon = new Coupon();
$userId = SessionManager::getCurrentUserId();
$userCoupons = $coupon->getUserCoupons($userId);
include __DIR__ . '/../includes/header.php';
?>
<div class="main-content-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold text-white mb-3">
                <i class="fas fa-ticket-alt me-3"></i>Kuponlarım
            </h1>
            <p class="lead text-white-50">Kullanabileceğiniz indirim kuponları</p>
        </div>
        <?php if (empty($userCoupons)): ?>
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-ticket-alt fa-5x text-muted mb-4"></i>
                            <h4 class="fw-bold mb-3">Henüz kuponunuz bulunmuyor</h4>
                            <p class="text-muted">Kullanabileceğiniz indirim kuponları burada görünecek.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($userCoupons as $couponData): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100" style="border-radius: 25px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.1)'">
                            <div class="card-header text-center py-4 position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 25px 25px 0 0; overflow: hidden;">
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);"></div>
                                <div class="position-relative">
                                    <div class="mb-3">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                                            <i class="fas fa-ticket-alt fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-white mb-2 fw-bold"><?php echo htmlspecialchars($couponData['code']); ?></h5>
                                    <?php if ($couponData['company_id'] === null): ?>
                                        <div class="badge bg-success px-3 py-2 rounded-pill" style="font-size: 0.9rem;">
                                            <i class="fas fa-globe me-1"></i>Tüm Firmalarda Geçerli
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-warning text-dark px-3 py-2 rounded-pill" style="font-size: 0.9rem;">
                                            <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($couponData['company_name'] ?? 'Firma Kuponu'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <div class="p-4 rounded-4" style="background: linear-gradient(135deg, #e8f5e8, #c8e6c9); border: 2px dashed #28a745;">
                                        <h2 class="text-success fw-bold mb-2" style="font-size: 2.5rem;">
                                            %<?php echo number_format($couponData['discount'], 0); ?>
                                        </h2>
                                        <h6 class="text-success fw-bold mb-0">İndirim</h6>
                                    </div>
                                </div>
                                <div class="text-center mb-4">
                                    <div class="p-3 rounded-3" style="background: linear-gradient(135deg, #fff3cd, #ffeaa7);">
                                        <i class="fas fa-calendar-alt text-warning fa-lg mb-2"></i>
                                        <h6 class="text-warning fw-bold mb-1">Son Kullanma</h6>
                                        <span class="fw-bold text-dark">
                                            <?php echo date('d.m.Y', strtotime($couponData['expire_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button class="btn btn-primary btn-lg w-100 rounded-pill" 
                                            style="background: linear-gradient(45deg, #007bff, #6610f2); border: none; box-shadow: 0 4px 15px rgba(0,123,255,0.3);"
                                            onclick="copyCouponCode('<?php echo $couponData['code']; ?>')">
                                        <i class="fas fa-copy me-2"></i>Kodu Kopyala
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="copyToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Başarılı!</strong>
                    </div>
                    <div class="toast-body">
                        Kupon kodu panoya kopyalandı!
                    </div>
                </div>
            </div>
            <script>
            function copyCouponCode(code) {
                navigator.clipboard.writeText(code).then(function() {
                    var toast = new bootstrap.Toast(document.getElementById('copyToast'));
                    toast.show();
                });
            }
            </script>
        <?php endif; ?>
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
    <script>
        function copyCouponCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                const toast = document.getElementById('copyToast');
                if (toast) {
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.show();
                }
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
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
            const inputs = document.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
