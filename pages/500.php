<?php
http_response_code(500);
$pageTitle = '500 - Sunucu Hatası';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <i class="fas fa-server fa-5x text-danger mb-4"></i>
                    <h1 class="display-1 fw-bold text-danger">500</h1>
                    <h2 class="mb-3">Sunucu Hatası</h2>
                    <p class="lead text-muted mb-4">
                        Sunucuda bir hata oluştu. Lütfen daha sonra tekrar deneyin.
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
