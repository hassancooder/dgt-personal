<?php
if (!(isset($ERR['code']) && isset($ERR['msg']) && isset($ERR['details']) && isset($ERR['url']))) {
    header('Location: ' . getRoot());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ERR['code'] . ' - ' . $ERR['msg']; ?></title>
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
            <h1 class="mt-5 text-[36px] font-bold text-slate-800 lg:text-[50px]"><?= $ERR['code'] . ' - ' . $ERR['msg']; ?></h1>
            <p class="text-slate-600 mt-3 mb-10 lg:text-lg"><?= $ERR['details']; ?></p>
            <a href="<?= $ERR['url']; ?>" type="button" class="group inline-flex items-center py-3 px-6 text-sm bg-slate-800 text-white border-2 border-slate-600 rounded-full cursor-pointer font-semibold text-center transition-all duration-300 hover:bg-slate-900 hover:border-slate-500">
                <i class="ri-arrow-left-line mr-2 transition-transform duration-300 group-hover:-translate-x-1"></i> Home Page
            </a>
        </div>
    </div>
</body>

</html>