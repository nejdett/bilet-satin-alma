<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/path_helpers.php';
SessionManager::startSession();
if (SessionManager::isLoggedIn()) {
    if (SessionManager::hasRole('admin')) {
        header('Location: admin_panel.php');
    } elseif (SessionManager::hasRole('company')) {
        header('Location: company_panel.php');
    } else {
        header('Location: ../index.php');
    }
    exit();
}
$pageTitle = 'Giriş Yap - Otobüs Bileti Satış Platformu';
$errors = [];
$formData = [];
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'no_company') {
        $errors[] = 'Hesabınız bir firmaya bağlı değil. Lütfen sistem yöneticisi ile iletişime geçin.';
    } elseif ($_GET['error'] === 'db_error') {
        $errors[] = 'Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.';
    }
}
$redirectUrl = $_GET['redirect'] ?? getAbsoluteUrl('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik hatası. Lütfen sayfayı yenileyin ve tekrar deneyin.';
    } else {
        $formData = [
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];
        if (empty($errors)) {
            $user = new User();
            $result = $user->login($formData['email'], $formData['password']);
            if ($result['success']) {
                if (SessionManager::hasRole('admin')) {
                    $redirectTo = 'admin_panel.php';
                } elseif (SessionManager::hasRole('company')) {
                    $redirectTo = 'company_panel.php';
                } else {
                    $redirectTo = filter_var($redirectUrl, FILTER_VALIDATE_URL) ? $redirectUrl : '../index.php';
                }
                header('Location: ' . $redirectTo);
                exit();
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="login-page-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 120px; padding-bottom: 50px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-lg border-0" style="border-radius: 15px; backdrop-filter: blur(10px);">
                    <div class="card-header text-center py-4" style="background: linear-gradient(45deg, #007bff, #6610f2); border-radius: 15px 15px 0 0;">
                        <div class="mb-3">
                            <i class="fas fa-bus fa-3x text-white"></i>
                        </div>
                        <h3 class="text-white mb-0 fw-bold">Giriş Yap</h3>
                        <p class="text-white-50 mb-0 mt-2">Hesabınıza giriş yapın</p>
                    </div>
                    <div class="card-body p-5">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3 text-danger"></i>
                                    <div>
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="" novalidate class="needs-validation">
                            <input type="hidden" name="csrf_token" value="<?php echo SessionManager::generateCSRFToken(); ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectUrl); ?>">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    E-posta Adresi <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;">
                                        <i class="fas fa-at text-muted"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control form-control-lg border-start-0 ps-0" 
                                           id="email" 
                                           name="email" 
                                           placeholder="ornek@email.com"
                                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                           style="border-radius: 0 10px 10px 0;"
                                           required
                                           autofocus>
                                </div>
                            </div>  
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-primary"></i>
                                    Şifre <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;">
                                        <i class="fas fa-key text-muted"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control form-control-lg border-start-0 ps-0" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Şifrenizi girin"
                                           style="border-radius: 0 10px 10px 0;"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius: 0 10px 10px 0;">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                        <label class="form-check-label text-muted" for="rememberMe">
                                            Beni hatırla
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <a href="#" class="text-decoration-none text-primary">
                                        <small>Şifremi unuttum</small>
                                    </a>
                                </div>
                            </div>
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg fw-semibold" style="border-radius: 10px; background: linear-gradient(45deg, #007bff, #6610f2);">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Giriş Yap
                                </button>
                            </div>
                        </form>
                        <div class="text-center mb-4">
                            <div class="d-flex align-items-center">
                                <hr class="flex-grow-1">
                                <span class="px-3 text-muted">veya</span>
                                <hr class="flex-grow-1">
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="mb-0 text-muted">
                                Hesabınız yok mu? 
                                <a href="register.php" class="text-decoration-none fw-semibold" style="color: #6610f2;">
                                    Hemen kayıt olun
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p class="text-white-50 mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Güvenli ve hızlı giriş sistemi
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    if (togglePassword && passwordInput && toggleIcon) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            if (type === 'password') {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        });
    }
    const card = document.querySelector('.card');
    if (card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    }
    const inputs = document.querySelectorAll('.form-control');
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
<style>
/* Custom styles for login page */
.login-page-wrapper {
    min-height: 100vh;
}
body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    min-height: 100vh;
}
.form-control:focus {
    border-color: #6610f2;
    box-shadow: 0 0 0 0.2rem rgba(102, 16, 242, 0.25);
}
.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
}
.card {
    background: rgba(255, 255, 255, 0.95);
}
.btn-primary {
    border: none;
    transition: all 0.3s ease;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}
@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
    .login-page-wrapper {
        padding-top: 100px;
        padding-bottom: 30px;
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>