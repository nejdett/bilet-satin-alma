<?php
require_once __DIR__ . '/../config/session.php';
SessionManager::startSession();
SessionManager::logout();
SessionManager::setFlashMessage('Başarıyla çıkış yaptınız.', 'success');
header('Location: ../index.php');
exit();
?>