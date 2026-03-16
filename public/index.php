<?php

require_once __DIR__.'/../vendor/autoload.php';

// Simple routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/RMS/public', '', $uri);

switch ($uri) {
    case '/':
    case '':
        header('Location: /RMS/public/login.php');
        exit;
    default:
        http_response_code(404);
        echo "404 - Page not found";
        break;
}