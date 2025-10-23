<?php
http_response_code(404);
$pageTitle = '404 - Sayfa Bulunamadı';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                    <h1 class="display-1 fw-bold text-primary">404</h1>
                    <h2 class="mb-3">Sayfa Bulunamadı</h2>
                    <p class="lead text-muted mb-4">
                        Aradığınız sayfa mevcut değil veya taşınmış olabilir.
                    </p>
                    <a href="<?php echo getAbsoluteUrl('index.php'); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
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
