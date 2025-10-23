<?php
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    return false;
});
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
?>
