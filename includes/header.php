<?php
require_once __DIR__ . '/path_helpers.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Otobüs Bileti Satış Platformu'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo getAbsoluteUrl('assets/css/style.min.css'); ?>" rel="stylesheet">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="icon" type="image/x-icon" href="<?php echo getAbsoluteUrl('assets/images/favicon.ico'); ?>">
</head>
<body style="margin: 0; padding: 0;">
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(45deg, #007bff, #6610f2); margin: 0; padding-top: 0;">
        <div class="container">
            <span class="navbar-brand fw-bold">
                Otobüs Bileti Platformu
            </span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (SessionManager::hasRole('user') || !SessionManager::isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../../index.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo '../index.php';
                                   } else {
                                       echo 'index.php';
                                   }
                               ?>">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (SessionManager::isLoggedIn() && SessionManager::hasRole('user')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_tickets.php') ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../my_tickets.php';
                                   } elseif (strpos($currentPath, '/pages/admin/') !== false) {
                                       echo '../my_tickets.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo 'my_tickets.php';
                                   } else {
                                       echo 'pages/my_tickets.php';
                                   }
                               ?>">
                                <i class="fas fa-ticket-alt me-1"></i>Biletlerim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_coupons.php') ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../my_coupons.php';
                                   } elseif (strpos($currentPath, '/pages/admin/') !== false) {
                                       echo '../my_coupons.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo 'my_coupons.php';
                                   } else {
                                       echo 'pages/my_coupons.php';
                                   }
                               ?>">
                                <i class="fas fa-tags me-1"></i>Kuponlarım
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (SessionManager::hasRole('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/admin/') !== false) {
                                       echo '../../index.php';
                                   } elseif (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../../index.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo '../index.php';
                                   } else {
                                       echo 'index.php';
                                   }
                               ?>">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_panel.php') !== false) ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/admin/') !== false) {
                                       echo '../admin_panel.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo 'admin_panel.php';
                                   } else {
                                       echo 'pages/admin_panel.php';
                                   }
                               ?>">
                                <i class="fas fa-cogs me-1"></i>Admin Paneli
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (SessionManager::hasRole('company')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../../index.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo '../index.php';
                                   } else {
                                       echo 'index.php';
                                   }
                               ?>">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'company_panel.php') !== false) ? 'active' : ''; ?>" 
                               href="<?php 
                                   $currentPath = $_SERVER['PHP_SELF'];
                                   if (strpos($currentPath, '/pages/company/') !== false) {
                                       echo '../company_panel.php';
                                   } elseif (strpos($currentPath, '/pages/') !== false) {
                                       echo 'company_panel.php';
                                   } else {
                                       echo 'pages/company_panel.php';
                                   }
                               ?>">
                                <i class="fas fa-building me-1"></i>Firma Paneli
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
                                        <a class="dropdown-item" href="<?php 
                                            $currentPath = $_SERVER['PHP_SELF'];
                                            if (strpos($currentPath, '/pages/company/') !== false) {
                                                echo '../profile.php';
                                            } elseif (strpos($currentPath, '/pages/admin/') !== false) {
                                                echo '../profile.php';
                                            } elseif (strpos($currentPath, '/pages/') !== false) {
                                                echo 'profile.php';
                                            } else {
                                                echo 'pages/profile.php';
                                            }
                                        ?>">
                                            <i class="fas fa-user me-2"></i>Profil
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!SessionManager::hasRole('admin')): ?>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php 
                                        $currentPath = $_SERVER['PHP_SELF'];
                                        if (strpos($currentPath, '/pages/admin/') !== false) {
                                            echo '../logout.php';
                                        } elseif (strpos($currentPath, '/pages/company/') !== false) {
                                            echo '../logout.php';
                                        } elseif (strpos($currentPath, '/pages/') !== false) {
                                            echo 'logout.php';
                                        } else {
                                            echo 'pages/logout.php';
                                        }
                                    ?>">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm me-2 px-3" href="<?php 
                                $currentPath = $_SERVER['PHP_SELF'];
                                if (strpos($currentPath, '/pages/') !== false) {
                                    echo 'login.php';
                                } else {
                                    echo 'pages/login.php';
                                }
                            ?>">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light btn-sm px-3" href="<?php 
                                $currentPath = $_SERVER['PHP_SELF'];
                                if (strpos($currentPath, '/pages/') !== false) {
                                    echo 'register.php';
                                } else {
                                    echo 'pages/register.php';
                                }
                            ?>" style="color: #007bff;">
                                <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
