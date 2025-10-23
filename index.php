<?php
require_once 'includes/autoload.php';
require_once 'classes/Trip.php';

SessionManager::startSession();

// Initialize trip class
$trip = new Trip();
$departureCities = $trip->getDepartureCities();
$destinationCities = $trip->getDestinationCities();

// Get popular routes for quick selection
// TODO: Cache this, it's called on every page load
$popularRoutes = $trip->getPopularRoutes(3);

$pageTitle = 'Ana Sayfa - Otobüs Bileti Satış Platformu';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.min.css?v=1.1" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(45deg, #007bff, #6610f2);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
               Otobüs Bileti Platformu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <?php if (SessionManager::isLoggedIn() && SessionManager::hasRole('user')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/my_tickets.php">
                                <i class="fas fa-ticket-alt me-1"></i>Biletlerim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/my_coupons.php">
                                <i class="fas fa-tags me-1"></i>Kuponlarım
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (SessionManager::hasRole('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/admin_panel.php">
                                <i class="fas fa-cogs me-1"></i>Admin Paneli
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (SessionManager::isLoggedIn()): ?>
                        <?php $currentUser = SessionManager::getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php 
                                if ($currentUser && isset($currentUser['fullname'])) {
                                    echo htmlspecialchars(explode(' ', $currentUser['fullname'])[0]);
                                } else {
                                    echo 'Kullanıcı';
                                }
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (!SessionManager::hasRole('admin')): ?>
                                <li>
                                    <a class="dropdown-item" href="pages/profile.php">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (SessionManager::hasRole('company')): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-primary" href="pages/company_panel.php">
                                            <i class="fas fa-building me-2"></i>Firma Paneli
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="pages/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm me-2 px-3" href="pages/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light btn-sm px-3" href="pages/register.php" style="color: #007bff;">
                                <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
<div class="main-content-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <?php 
        if (function_exists('displayFlashMessages')) {
            displayFlashMessages(); 
        }
        ?>
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold text-white mb-3" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                Otobüs Bileti Al
            </h1>
            <p class="lead text-white-50 mb-5" style="font-size: 1.3rem;">
                Türkiye'nin her yerine güvenli ve konforlu yolculuk
            </p>
        </div>
        <div class="row justify-content-center mb-5">
            <div class="col-12 col-lg-10 col-xl-8">
                <div class="card shadow-lg border-0" style="border-radius: 20px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 20px 20px 0 0;">
                        <div class="mb-2">
                            <i class="fas fa-search fa-2x text-white"></i>
                        </div>
                        <h3 class="text-white mb-0 fw-bold">Sefer Arama</h3>
                        <p class="text-white-50 mb-0 mt-2">Yolculuğunuzu planlayın</p>
                    </div>
                    <div class="card-body p-5">
                        <form method="GET" action="pages/search.php" class="needs-validation" novalidate>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-semibold mb-3">
                                        <i class="fas fa-map-marker-alt text-success me-2"></i>
                                        Nereden
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0" style="border-radius: 15px 0 0 15px;">
                                            <i class="fas fa-bus text-success"></i>
                                        </span>
                                        <select class="form-select form-select-lg border-start-0" name="departure" required style="border-radius: 0 15px 15px 0;">
                                            <option value="">Kalkış şehri seçin</option>
                                            <?php 
                                            if (!empty($departureCities)) {
                                                foreach ($departureCities as $city): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($city); ?>">
                                                    <?php echo htmlspecialchars($city); ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            } else {
                                                $fallbackCities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Adana'];
                                                foreach ($fallbackCities as $city):
                                            ?>
                                                <option value="<?php echo htmlspecialchars($city); ?>">
                                                    <?php echo htmlspecialchars($city); ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-3">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                        Nereye
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0" style="border-radius: 15px 0 0 15px;">
                                            <i class="fas fa-map-marker-alt text-danger"></i>
                                        </span>
                                        <select class="form-select form-select-lg border-start-0" name="arrival" required style="border-radius: 0 15px 15px 0;">
                                            <option value="">Varış şehri seçin</option>
                                            <?php 
                                            if (!empty($destinationCities)) {
                                                foreach ($destinationCities as $city): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($city); ?>">
                                                    <?php echo htmlspecialchars($city); ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            } else {
                                                $fallbackCities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Adana'];
                                                foreach ($fallbackCities as $city):
                                            ?>
                                                <option value="<?php echo htmlspecialchars($city); ?>">
                                                    <?php echo htmlspecialchars($city); ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-semibold mb-3">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        Gidiş Tarihi
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0" style="border-radius: 15px 0 0 15px;">
                                            <i class="fas fa-calendar text-primary"></i>
                                        </span>
                                        <input type="date" class="form-control form-control-lg border-start-0" name="date" 
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               value="<?php echo date('Y-m-d'); ?>" 
                                               style="border-radius: 0 15px 15px 0;"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-lg w-100 fw-semibold search-btn" 
                                            style="border-radius: 15px; background: linear-gradient(45deg, #28a745, #20c997); border: none; height: 58px;">
                                        <i class="fas fa-search me-2"></i>
                                        <span class="btn-text">Sefer Ara</span>
                                        <div class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                            <span class="visually-hidden">Aranıyor...</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-12">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; background: rgba(255, 255, 255, 0.9);">
                            <div class="card-body text-center p-4">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-shield-alt fa-3x text-success"></i>
                                </div>
                                <h5 class="fw-bold mb-3">Güvenli Ödeme</h5>
                                <p class="text-muted">SSL sertifikası ile korumalı ödeme sistemi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; background: rgba(255, 255, 255, 0.9);">
                            <div class="card-body text-center p-4">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-clock fa-3x text-primary"></i>
                                </div>
                                <h5 class="fw-bold mb-3">7/24 Destek</h5>
                                <p class="text-muted">Her zaman yanınızdayız, yardıma ihtiyacınız olduğunda</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; background: rgba(255, 255, 255, 0.9);">
                            <div class="card-body text-center p-4">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-mobile-alt fa-3x text-info"></i>
                                </div>
                                <h5 class="fw-bold mb-3">Mobil Uyumlu</h5>
                                <p class="text-muted">Her cihazdan kolayca bilet alabilirsiniz</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!SessionManager::isLoggedIn()): ?>
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95);">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-user-circle text-primary fa-4x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Hesabınız var mı?</h4>
                        <p class="text-muted mb-4 lead">
                            Giriş yaparak bilet satın alabilir, rezervasyonlarınızı takip edebilirsiniz.
                        </p>
                        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                            <a href="pages/login.php" class="btn btn-primary btn-lg px-5 fw-semibold" 
                               style="border-radius: 15px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                            <a href="pages/register.php" class="btn btn-outline-light btn-lg px-5 fw-semibold" 
                               style="border-radius: 15px; border-color: #fff; color: #6610f2;">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        .form-control:focus, .form-select:focus {
            border-color: #6610f2;
            box-shadow: 0 0 0 0.2rem rgba(102, 16, 242, 0.25);
        }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .form-control.is-valid, .form-select.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .search-error-alert {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .search-btn {
            transition: all 0.3s ease;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        .quick-route {
            transition: all 0.2s ease;
        }
        .quick-route:hover {
            transform: scale(1.05);
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
        .hero-icon i {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
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
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            const quickRouteButtons = document.querySelectorAll('.quick-route');
            const departureSelect = document.querySelector('select[name="departure"]');
            const arrivalSelect = document.querySelector('select[name="arrival"]');
            quickRouteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const from = this.getAttribute('data-from');
                    const to = this.getAttribute('data-to');
                    if (departureSelect && arrivalSelect) {
                        departureSelect.value = from;
                        arrivalSelect.value = to;
                        this.classList.add('btn-primary');
                        this.classList.remove('btn-outline-primary');
                        setTimeout(() => {
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-outline-primary');
                        }, 1000);
                    }
                });
            });
            const searchForm = document.querySelector('.needs-validation');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const departureSelect = document.querySelector('select[name="departure"]');
                    const arrivalSelect = document.querySelector('select[name="arrival"]');
                    const dateInput = document.querySelector('input[name="date"]');
                    const searchBtn = document.querySelector('.search-btn');
                    const btnText = searchBtn.querySelector('.btn-text');
                    const spinner = searchBtn.querySelector('.spinner-border');
                    clearErrorMessages();
                    let hasErrors = false;
                    const errors = [];
                    if (!departureSelect.value) {
                        errors.push('Kalkış şehri seçiniz.');
                        departureSelect.classList.add('is-invalid');
                        hasErrors = true;
                    } else {
                        departureSelect.classList.remove('is-invalid');
                        departureSelect.classList.add('is-valid');
                    }
                    if (!arrivalSelect.value) {
                        errors.push('Varış şehri seçiniz.');
                        arrivalSelect.classList.add('is-invalid');
                        hasErrors = true;
                    } else {
                        arrivalSelect.classList.remove('is-invalid');
                        arrivalSelect.classList.add('is-valid');
                    }
                    if (departureSelect.value && arrivalSelect.value) {
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
                        const normalizedDeparture = normalizeTurkish(departureSelect.value);
                        const normalizedArrival = normalizeTurkish(arrivalSelect.value);
                        if (normalizedDeparture === normalizedArrival) {
                            errors.push('Kalkış ve varış şehri aynı olamaz.');
                            departureSelect.classList.add('is-invalid');
                            arrivalSelect.classList.add('is-invalid');
                            hasErrors = true;
                        }
                    }
                    if (!dateInput.value) {
                        errors.push('Gidiş tarihi seçiniz.');
                        dateInput.classList.add('is-invalid');
                        hasErrors = true;
                    } else {
                        const selectedDate = new Date(dateInput.value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        if (selectedDate < today) {
                            errors.push('Geçmiş bir tarih seçemezsiniz.');
                            dateInput.classList.add('is-invalid');
                            hasErrors = true;
                        } else {
                            dateInput.classList.remove('is-invalid');
                            dateInput.classList.add('is-valid');
                        }
                    }
                    if (hasErrors) {
                        showErrorMessages(errors);
                        if (searchBtn && btnText && spinner) {
                            btnText.textContent = 'Sefer Ara';
                            spinner.classList.add('d-none');
                            searchBtn.disabled = false;
                        }
                        const errorAlert = document.querySelector('.search-error-alert');
                        if (errorAlert) {
                            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return false;
                    }
                    if (searchBtn && btnText && spinner) {
                        btnText.textContent = 'Aranıyor...';
                        spinner.classList.remove('d-none');
                        searchBtn.disabled = true;
                    }
                    setTimeout(() => {
                        this.submit();
                    }, 500);
                });
            }
            function showErrorMessages(errors) {
                const searchCard = document.querySelector('.card-body');
                if (!searchCard) return;
                const existingAlert = document.querySelector('.search-error-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show search-error-alert mb-4';
                errorAlert.style.borderRadius = '15px';
                let errorHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-2">Arama Hatası</h5>
                            <ul class="mb-0">
                `;
                errors.forEach(error => {
                    errorHTML += `<li>${error}</li>`;
                });
                errorHTML += `
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                errorAlert.innerHTML = errorHTML;
                searchCard.insertBefore(errorAlert, searchCard.firstChild);
                setTimeout(() => {
                    if (errorAlert && errorAlert.parentNode) {
                        const bsAlert = new bootstrap.Alert(errorAlert);
                        bsAlert.close();
                    }
                }, 8000);
            }
            function clearErrorMessages() {
                const existingAlert = document.querySelector('.search-error-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                const inputs = document.querySelectorAll('.form-control, .form-select');
                inputs.forEach(input => {
                    input.classList.remove('is-invalid', 'is-valid');
                });
            }
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