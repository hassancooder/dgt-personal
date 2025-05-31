<?php
require_once 'includes/functions.php';

if (!loadEnv(__DIR__ . '/.env')) {
    showErrorPage(400, 'ENV File Missing', '.env file is not found or readable');
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = substr($requestUri, strlen($scriptName));
$path = trim($path, '/');

$baseDir = __DIR__ . '/public';

if ($path === '') {
    define('PAGE_TO_RENDER', 'index');
    $segments = [''];
    define('SEGMENTS', $segments);
} else {
    $segments = explode('/', $path);
    define('SEGMENTS', $segments);

    $foundPage = false;

    for ($i = count($segments); $i > 0; $i--) {
        $tryPath = implode('/', array_slice($segments, 0, $i));

        $tryFile = $baseDir . '/' . $tryPath . '.php';
        if (is_file($tryFile)) {
            define('PAGE_TO_RENDER', $tryPath);
            $foundPage = true;
            break;
        }

        $tryIndex = $baseDir . '/' . $tryPath . '/index.php';
        if (is_file($tryIndex)) {
            define('PAGE_TO_RENDER', $tryPath . '/index');
            $foundPage = true;
            break;
        }
    }

    if (!$foundPage) {
        showErrorPage(404, 'Page Not Found', "The page you're looking for might have been moved, deleted, or temporarily unavailable.");
        exit;
    }
}
checkRouteAccess($publicPaths);
ob_start();
require_once 'includes/layout.php';
ob_end_flush();
