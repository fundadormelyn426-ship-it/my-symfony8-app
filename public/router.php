<?php
// public/router.php

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;

    // Serve static files directly (CSS, JS, images)
    if ($path !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

// Otherwise, use Symfony's front controller
require_once __DIR__ . '/index.php';