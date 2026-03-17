<?php

require_once __DIR__.'/../vendor/autoload.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/RMS/public', '', $uri);

// If a real .php file is being requested directly, serve it
$filePath = __DIR__ . $uri;
if ($uri !== '/' && $uri !== '' && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    require $filePath;
    exit;
}

// App routing
switch ($uri) {
    case '/':
    case '':
        header('Location: /RMS/public/login.php');
        exit;
    case '/login':
        require __DIR__ . '/login.php';
        break;
    case '/register':
        require __DIR__ . '/register.php';
        break;
    case '/dashboard':
        require __DIR__ . '/dashboard.php';
        break;
    case '/pos':
        require __DIR__ . '/pos.php';
        break;
    default:
        http_response_code(404);
        echo "404 - Page not found";
        break;
}
