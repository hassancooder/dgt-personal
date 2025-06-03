<?php
require_once __DIR__ . '/classes.php';

$publicPaths = ['auth', 'export'];
$alertStyles = [
    'success' => [
        'icon' => 'ri-check-line',
        'color' => 'text-green-600',
        'bg' => 'bg-green-100',
        'border' => 'border-green-200',
        'text' => 'text-green-700'
    ],
    'warning' => [
        'icon' => 'ri-alert-line',
        'color' => 'text-yellow-600',
        'bg' => 'bg-yellow-100',
        'border' => 'border-yellow-200',
        'text' => 'text-yellow-700'
    ],
    'error' => [
        'icon' => 'ri-close-line',
        'color' => 'text-red-600',
        'bg' => 'bg-red-100',
        'border' => 'border-red-200',
        'text' => 'text-red-700'
    ],
    'info' => [
        'icon' => 'ri-information-line',
        'color' => 'text-blue-600',
        'bg' => 'bg-blue-100',
        'border' => 'border-blue-200',
        'text' => 'text-blue-700'
    ]
];
$roleStyles = [
    'superadmin' => ['text' => 'Super Admin', 'bg' => 'bg-purple-100', 'textColor' => 'text-purple-800'],
    'admin'      => ['text' => 'Admin', 'bg' => 'bg-blue-100', 'textColor' => 'text-blue-800'],
    'manager'    => ['text' => 'Manager', 'bg' => 'bg-green-100', 'textColor' => 'text-green-800'],
    'staff'      => ['text' => 'Staff', 'bg' => 'bg-yellow-100', 'textColor' => 'text-yellow-800'],
    'customer'   => ['text' => 'Customer', 'bg' => 'bg-textSecondary/10', 'textColor' => 'text-textPrimary'],
    'agent'      => ['text' => 'Agent', 'bg' => 'bg-pink-100', 'textColor' => 'text-pink-800'],
];
function showSetupPage($errorMsg)
{
    $setup_url = getRoot() . 'setup.php';
    echo '<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Required!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css">
</head>

<body>
    <div class="flex h-[calc(100vh-80px)] items-center justify-center p-5 w-full bg-white">
        <div class="text-center">
            <div class="inline-flex rounded-full bg-red-100 p-4">
                <div class="rounded-full stroke-red-600 bg-red-200 p-4">
                    <svg class="w-16 h-16" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 8H6.01M6 16H6.01M6 12H18C20.2091 12 22 10.2091 22 8C22 5.79086 20.2091 4 18 4H6C3.79086 4 2 5.79086 2 8C2 10.2091 3.79086 12 6 12ZM6 12C3.79086 12 2 13.7909 2 16C2 18.2091 3.79086 20 6 20H14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M17 16L22 21M22 16L17 21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </div>
            <h1 class="mt-5 text-[36px] font-bold text-slate-800 lg:text-[50px]">Setup Requried!</h1>
            <p class="text-slate-600 mt-3 mb-10 lg:text-lg">' . $errorMsg . '</p>
            <form action="' . $setup_url . '" method="post">
                <input type="hidden" name="' . base64_encode('DB_HOST') . '" value="' . base64_encode($_ENV['DB_HOST']) . '">
                <input type="hidden" name="' . base64_encode('DB_NAME') . '" value="' . base64_encode($_ENV['DB_NAME']) . '">
                <input type="hidden" name="' . base64_encode('DB_USER') . '" value="' . base64_encode($_ENV['DB_USER']) . '">
                <input type="hidden" name="' . base64_encode('DB_PASS') . '" value="' . base64_encode($_ENV['DB_PASS']) . '">
                <input type="hidden" name="' . base64_encode('ROOT') . '" value="' . base64_encode($_ENV['ROOT']) . '">
            <button type="submit" class="group inline-flex items-center py-3 px-6 text-sm bg-slate-800 text-white border-2 border-slate-600 rounded-full cursor-pointer font-semibold text-center transition-all duration-300 hover:bg-slate-900 hover:border-slate-500"> Start Setup
            </button>
</form>
        </div>
    </div>
</body>

</html>';
    exit;
}
function renderMenu($items, $currentSlug, $isNavPage = false)
{
    echo '<ul class="space-y-2 font-medium flex-1">';
    foreach ($items as $index => $item) {
        $hasChildren = !empty($item['children']);
        $urlBase = $_ENV['ROOT'] . $item['full_slug'];

        // Active check
        $isActive = $item['full_slug'] === $currentSlug;
        $isParentOfActive = str_starts_with($currentSlug, $item['full_slug'] . '/');

        // Active classes
        $activeClasses = $isActive ? 'bg-accent/20 !text-accent font-semibold' : 'text-textPrimary';

        if ($isNavPage) {
            echo '<li><div class="flex justify-between items-center p-2 rounded-lg hover:bg-textSecondary/20"><span class="underline"><b class="font-semibold">' . ($index + 1) . '.</b> ' . ucfirst($item['label']) . '</span><div class="flex gap-3 items-center">' . App::icon('edit', '?id=' . $item['id']) . ($item['label'] !== 'Dashboard' ? App::icon('delete', App::currentPath() . '/action?task=nav_delete&id=' . $item['id']) : '') . '</div></div>';
            if ($hasChildren) {
                echo '<ul class="!pl-6 space-y-1 mt-1">';
                renderMenu($item['children'], $currentSlug, true);
                echo '</ul>';
            }
            echo '</li>';
        } else {
            // Normal dropdown menu with Alpine.js
            $alpineInit = $hasChildren ? "x-data=\"{ open: " . ($isActive || $isParentOfActive ? 'true' : 'false') . " }\"" : "";
            echo '<li ' . $alpineInit . '>';
            $url = $hasChildren ? '#' : $urlBase;
            echo '<a href="' . $url . '" ' . ($hasChildren ? '@click.prevent="open = !open"' : '') . ' 
                  class="flex justify-between items-center p-2 rounded-lg hover:bg-textSecondary/20 group cursor-pointer ' . $activeClasses . '">';
            echo '<span class="ms-3">' . ucfirst($item['label']) . '</span>';

            if ($hasChildren) {
                echo '<i :class="open ? \'ri-arrow-down-s-line rotate-0\' : \'ri-arrow-right-s-line\'" class="text-xl transition-all duration-300"></i>';
            }
            echo '</a>';

            if ($hasChildren) {
                echo '<ul x-show="open" x-collapse class="pl-4 space-y-1 mt-1" x-cloak>';
                renderMenu($item['children'], $currentSlug);
                echo '</ul>';
            }
            echo '</li>';
        }
    }
    echo '</ul>';
}
function checkRouteAccess($publicPaths = [])
{
    App::checkLogin();
    DB::con();
    DB::setupCheck();
    $CU = App::currentUser() ?? ['role' => ''];
    $userPaths = $CU['allowed_routes'] ?? [];
    $userPaths = [...$userPaths, ...$publicPaths];
    if (in_array($CU['role'], ['admin', 'superadmin'])) {
        $userPaths[] = 'users';
        if ($CU['role'] === 'superadmin') {
            $userPaths[] = 'navbar';
        }
    }
    $path = str_replace(($_ENV['ROOT'] == '/' ? '' : $_ENV['ROOT']), '', App::currentPath());
    $segments = array_values(array_filter(explode('/', $path))); // ['settings', 'users', 'add']

    if (!empty($segments)) {
        $placeholders = implode(',', array_fill(0, count($segments), '?'));
        $query = "SELECT label, slug FROM navbar WHERE slug IN ($placeholders)";
        $results = DB::query($query, $segments);
    } else {
        $results = [];
    }

    $labelMap = [];
    foreach ($results as $row) {
        $labelMap[$row['slug']] = $row['label'];
    }
    Doc::setTANDBC($segments, $labelMap);
    $pathSegment = $segments[0] ?? '';
    if (!in_array($pathSegment, $userPaths)) {
        showErrorPage(403, 'Page Restricted!', "Sorry! You Don't Have Access to This Page");
        exit;
    }
}


function buildTreeWithPaths(array $elements, $parentId = 0, $parentSlug = '')
{
    $branch = [];

    foreach ($elements as $element) {
        if (!in_array($element['slug'], App::currentUser()['allowed_routes'])) {
            continue;
        }
        if ((int)$element['parent_id'] === (int)$parentId) {
            $currentSlug = $element['slug'];
            $fullSlug = trim($parentSlug . '/' . $currentSlug, '/');

            $element['full_slug'] = $fullSlug;

            $children = buildTreeWithPaths($elements, $element['id'], $fullSlug);
            $element['children'] = $children;

            $branch[] = $element;
        }
    }

    usort($branch, fn($a, $b) => $a['position'] <=> $b['position']);

    return $branch;
}

// ======== Utilities ========
function getRoot(): string
{
    return rtrim($_ENV['ROOT'] ?? __DIR__ . '/');
}

function ensureLogsDirectory(): string
{
    $logsPath = $_SERVER['DOCUMENT_ROOT'] . $_ENV['ROOT'] . 'logs';
    if (!is_dir($logsPath)) {
        mkdir($logsPath, 0755, true);
    }
    return $logsPath;
}

function logError(string $message, string $type = 'runtime'): void
{
    $logsPath = ensureLogsDirectory();
    $filename = $type === 'db' ? 'db-logs.log' : 'runtime-logs.log';
    $filePath = "$logsPath/$filename";
    $date = date('Y-m-d H:i:s');
    $log = "[$date] $message" . PHP_EOL;
    file_put_contents($filePath, $log, FILE_APPEND);
}

function showErrorPage($code = 404, $msg = null, $details = null, $url = null): void
{
    $ERR = [
        'code' => $code,
        'msg' => $msg ?? 'Page Not Found',
        'details' => $details ?? "The page you're looking for might have been moved, deleted, or temporarily unavailable.",
        'url' => $url ?? getRoot()
    ];
    include __DIR__ . '/../public/error-page.php';
    exit;
}

// ======== Exception & Error Handlers ========
set_exception_handler(function ($e) {
    $message = "UNCAUGHT EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();

    // echo "<pre style='color:red; font-weight:bold;'>$message</pre>"; // 👈 For debugging
    logError($message, 'runtime');

    showErrorPage(500, "Unexpected Error", $e->getMessage());
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $message = "ERROR [$errno]: $errstr in $errfile on line $errline";

    // echo "<pre style='color:orange; font-weight:bold;'>$message</pre>"; // 👈 For debugging
    logError($message, 'runtime');

    showErrorPage(500, "System Error", "$errstr in $errfile on line $errline");
});

/*
ci Means Clean Input
@params
input sting

jd Json_decode ASSOCIATIVE ARRAY
je json
*/
function ci($input)
{
    return mysqli_real_escape_string(DB::$conn, $input);
}
function jd($input, $returnObj = false)
{
    return $returnObj ? json_decode($input) : json_decode($input, true);
}
function je($input)
{
    return json_encode($input);
}
function vd($input)
{
    return var_dump($input);
}

/*
showMsg($type, $msg, $redirect);
@params {
    $type = ('success','warning','error') @string
    $msg =  any @sting text
    $redirect = url to send user back to that page
}
*/

function showMsg($type, $msg, $redirect = null)
{
    $_SESSION['alert'] = [
        'defined' => true,
        'type' => $type,
        'msg' => $msg
    ];
    header('location: ' . getRoot() . $redirect);
}
function ddate($date, $type = "datetime")
{
    $timestamp = strtotime($date); // Convert to timestamp
    switch ($type) {
        case 'half-month':
            return date('M d, Y', $timestamp); // Mar 17, 2025

        case 'full-month':
            return date('F d, Y', $timestamp); // March 17, 2025

        case 'slash':
            return date('d/m/Y', $timestamp); // 17/03/2025

        case 'dash':
            return date('d-m-Y', $timestamp); // 17-03-2025

        case 'iso':
            return date('Y-m-d', $timestamp); // 2025-03-17

        case 'time':
            return date('h:i A', $timestamp); // 09:20 AM

        case 'datetime':
            return date('F d, Y - h:i A', $timestamp); // March 17, 2025 - 09:20 AM

        case 'short-date-time':
            return date('d/m/Y h:i A', $timestamp); // 17/03/2025 09:20 AM

        case 'db':
            return date('Y-m-d H:i:s', $timestamp); // 2025-03-17 09:20:33 (same as input)
        default:
            // custom format passed as type
            return date($type, $timestamp);
    }
}
