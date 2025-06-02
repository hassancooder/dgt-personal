<?php
define('ALERT', $_SESSION['alert'] ?? []);
if (isset($_SESSION['alert'])) {
    unset($_SESSION['alert']);
}
ob_start(); // Start output buffer for page content
if (file_exists(__DIR__ . '/../public/' . PAGE_TO_RENDER . '.php')) {
    define('RENDER', __DIR__ . '/../public/' . PAGE_TO_RENDER . '.php');
} else {
    define('RENDER', __DIR__ . '/../public/' . PAGE_TO_RENDER . '/index.php');
}
$pageWithoutSidebar = (SEGMENTS[0] === 'auth' && SEGMENTS[1] === 'login') || (SEGMENTS[0] === 'auth' && SEGMENTS[1] === 'forget-password');
if (App::currentUser()) {
    $color_theme = App::currentUser()['preferences']['color_theme'];
} else {
    $color_theme = App::$defaultTheme;
}
?>

<?php include RENDER; ?>
<?php $pageContent = ob_get_clean(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Doc::renderTitle(); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="<?= Dir::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= Dir::getAsset('css/custom.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <style type="text/tailwindcss">
        @theme {
            --color-primary: #2563eb;
            <?php foreach ($color_theme as $key => $color) {
                echo '--color-' . $key . ': ' . $color . ';';
            } ?>
            --font-family-display: "Barlow", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .myinput {
            @apply w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition rounded-lg;
        }
        * {
            font-family: "Barlow", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
      margin: 0;
      height: 100vh;
      overflow: hidden; /* Prevent body scroll */
    }
    body, html{
        scroll-behavior: smooth;
    }
    #content-area {
      height: calc(100vh - 64px); /* Adjust for topbar height */
      overflow-y: auto; /* Only content area scrolls */
    }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.2/dist/cdn.min.js" defer></script>
    <?= Doc::renderHeadLinks(); ?>
</head>

<body class="bg-bg text-textPrimary" x-data="{ sidebarOpen: false, dropdownOpen: false }">
    <!-- Toast Notification -->
    <?php if (!empty(ALERT) && ALERT['defined']) :
        $type = ALERT['type'] ?? 'info';
        $alert = $alertStyles[$type] ?? $alertStyles['info'];
    ?>
        <div id="custom-toast" class="fixed bottom-[-100px] right-6 max-w-xs transition-all duration-500 ease-out 
        <?= $alert['bg'] ?> <?= $alert['border'] ?> border-2 rounded-lg px-4 py-3 flex items-center space-x-3 z-50 opacity-0">
            <div class="flex-shrink-0 w-6 h-6 p-0 m-0 flex items-center justify-center <?= $alert['color'] ?>">
                <i class="<?= $alert['icon'] ?> text-lg"></i>
            </div>
            <div class="flex-1 text-sm font-medium font-display <?= $alert['text'] ?>">
                <?= htmlspecialchars(ALERT['msg'] ?? 'Something happened') ?>
            </div>
            <button class="text-<?= $alert['color'] ?> ml-2 mt-1" onclick="hideToast()">
                <i class="ri-close-line text-lg <?= $alert['color'] ?>"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($pageWithoutSidebar) { ?>
        <!-- Render pages without sidebar (e.g., login, forget-password) -->
        <div class="min-h-screen">
            <?= $pageContent; ?>
        </div>
    <?php } else { ?>
        <!-- Fixed Topbar -->
        <nav class="fixed top-0 z-50 w-full bg-bg border-b border-textSecondary/20">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <!-- Sidebar Toggle Button for Mobile -->
                        <button
                            @click="sidebarOpen = !sidebarOpen"
                            class="inline-flex items-center p-2 text-sm text-textSecondary rounded-lg sm:hidden hover:bg-textSecondary/10 focus:outline-none focus:ring-2 focus:ring-textSecondary/10">
                            <span class="sr-only">Open sidebar</span>
                            <i class="ri-menu-line w-5 h-5 text-xl flex justify-center items-center cursor-pointer"></i>
                        </button>
                        <a href="<?= getRoot(); ?>" class="flex ms-2 md:me-24">
                            <img src="https://flowbite.com/docs/images/logo.svg" class="h-8 me-3" alt="FlowBite Logo" />
                            <span class="self-center text-xl font-bold sm:text-2xl whitespace-nowrap text-textPrimary"><?= $_ENV['APP_DISPLAY_NAME']; ?></span>
                        </a>
                    </div>
                    <!-- Profile Dropdown -->
                    <div class="flex items-center">
                        <div class="flex items-center ms-3">
                            <div>
                                <button
                                    @click="dropdownOpen = !dropdownOpen"
                                    class="flex text-sm bg-textSecondary rounded-full focus:ring-4 focus:ring-textSecondary/10"
                                    aria-expanded="false">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="w-8 h-8 rounded-full border border-textSecondary/10 cursor-pointer" src="<?= Dir::getAsset(!empty($_SESSION['user']['image']) ? 'uploads/' . $_SESSION['user']['image'] : 'images/dummy-user-image.jpg'); ?>" alt="user photo">
                                </button>
                            </div>
                            <div
                                x-show="dropdownOpen"
                                @click.away="dropdownOpen = false"
                                class="absolute top-12 right-4 z-50 my-4 text-base list-none bg-bg divide-y divide-textSecondary/20 rounded-sm border border-textSecondary/20 w-[180px]">
                                <div class="px-4 py-3">
                                    <p class="text-sm font-medium text-textPrimary">
                                        <?= $_SESSION['user']['name'] ?? 'Unknown'; ?>
                                    </p>
                                    <p class="text-sm font-medium text-textSecondary truncate">
                                        @<?= $_SESSION['user']['username'] ?? 'unknown'; ?>
                                    </p>
                                </div>
                                <ul class="py-1">
                                    <?php if ($_SESSION['user']['role'] === 'superadmin') { ?>
                                        <li>
                                            <a href="<?= getRoot() . 'settings/navbar'; ?>" class="block px-4 py-2 text-sm font-medium text-textSecondary hover:bg-textSecondary/20">Navbar</a>
                                        </li>
                                        <li>
                                            <a href="<?= getRoot() . 'settings/static-types'; ?>" class="block px-4 py-2 text-sm font-medium text-textSecondary hover:bg-textSecondary/20">Static Types</a>
                                        </li>
                                    <?php } ?>
                                    <li>
                                        <a href="<?= getRoot() . 'settings/profile'; ?>" class="block px-4 py-2 text-sm font-medium text-textSecondary hover:bg-textSecondary/20">Profile</a>
                                    </li>
                                    <li>
                                        <a href="<?= getRoot() . 'settings'; ?>" class="block px-4 py-2 text-sm font-medium text-textSecondary hover:bg-textSecondary/20">Settings</a>
                                    </li>
                                    <li>
                                        <a href="<?= getRoot() . 'auth/logout'; ?>" class="block px-4 py-2 text-sm font-medium text-textSecondary hover:bg-textSecondary/20">Sign out</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Fixed Sidebar -->
        <div class="flex pt-14">
            <aside
                id="logo-sidebar"
                class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform bg-bg border-r border-textSecondary/20 sm:translate-x-0"
                :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
                aria-label="Sidebar">

                <div class="h-full px-3 pb-4 flex flex-col bg-bg">
                    <?php
                    $currentSlug = implode('/', SEGMENTS);
                    $allItems = DB::fetch('navbar', [], 'all');
                    $menuTree = buildTreeWithPaths($allItems);
                    renderMenu($menuTree, $currentSlug);
                    ?>
                    <!-- Fixed Buttons at Bottom -->
                    <div class="mt-auto border-t-2 pt-1 border-textSecondary/20">
                        <a href="<?= getRoot() . 'settings'; ?>" class="flex items-center p-2 text-textPrimary rounded-lg hover:bg-textSecondary/20 group <?= SEGMENTS[0] === 'settings' ? 'bg-accent/20 text-accent' : '';  ?>">
                            <i class="ri-settings-3-line w-5 h-5  <?= SEGMENTS[0] === 'settings' ? 'text-accent' : 'text-textPrimary';  ?> transition duration-75 group-hover:text-textPrimary"></i>
                            <span class="flex-1 ms-3 whitespace-nowrap group-hover:text-textPrimary <?= SEGMENTS[0] === 'settings' ? 'text-accent' : 'text-textPrimary';  ?> font-medium">Settings</span>
                        </a>
                        <a href="<?= getRoot() . 'auth/logout'; ?>" class="flex items-center p-2 text-textPrimary rounded-lg hover:bg-textSecondary/20 group">
                            <i class="ri-logout-box-line w-5 h-5 text-textPrimary transition duration-75 group-hover:text-textPrimary"></i>
                            <span class="flex-1 ms-3 whitespace-nowrap text-textPrimary font-medium">Logout</span>
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Scrollable Content Area -->
            <main class="flex-1 sm:ml-62 px-4 py-1 bg-gray-50" id="content-area"> <!-- ml-64 to offset fixed sidebar -->
                <?= Doc::getBreadCrumb(); ?>
                <?= $pageContent; ?>
            </main>
        </div>
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="<?= Dir::getAsset('js/custom.js'); ?>" defer></script>
    <?= Doc::renderFooterScripts(); ?>
</body>

</html>