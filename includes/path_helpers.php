<?php
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $appDir = '';
    if (strpos($scriptName, '/') !== false) {
        $pathParts = explode('/', $scriptName);
        array_pop($pathParts);
        if (end($pathParts) === 'pages' || end($pathParts) === 'admin' || end($pathParts) === 'company') {
            array_pop($pathParts);
        }
        $appDir = implode('/', $pathParts);
    }
    return $protocol . '://' . $host . $appDir . '/';
}
function getAbsoluteUrl($path) {
    $baseUrl = getBaseUrl();
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, 'pages/') === 0 && strpos($baseUrl, '/pages/') !== false) {
        $cleanPath = substr($cleanPath, 6);
    }
    return $baseUrl . $cleanPath;
}
function getRelativePath($targetPath) {
    $currentDir = dirname($_SERVER['SCRIPT_NAME']);
    $currentDir = str_replace('\\', '/', $currentDir);
    if ($currentDir === '/' || $currentDir === '' || $currentDir === '.') {
        return $targetPath;
    }
    $currentDir = trim($currentDir, '/');
    $levelsUp = 0;
    if (!empty($currentDir)) {
        $levelsUp = substr_count($currentDir, '/') + 1;
    }
    if ($levelsUp > 0) {
        return str_repeat('../', $levelsUp) . $targetPath;
    }
    return $targetPath;
}
?>