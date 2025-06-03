<?php
if (isset($_POST['LoginSubmit'])) {
    $user_in_db = DB::fetch('users', ['username' => ci($_POST['username'])], 'one');
    $return_url = !empty($_SESSION['return_url']) ? $_SESSION['return_url'] : '';
    $return_url = $return_url === 'auth/login' ? $_ENV['ROOT'] : $return_url;
    if ($user_in_db) {
        if ($_POST['password'] === App::decrypt($user_in_db['password'])) {
            if (!(int)$user_in_db['is_active']) {
                showMsg('warning', "! Sorry: " . ucfirst($user_in_db['name']) . ", your account is locked!", App::currentPath(true, true));
                exit;
            }
            if (isset($_POST['remember_me'])) {
                $lifetime = 60 * 60 * 24 * 7;
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }
                session_set_cookie_params([
                    'lifetime' => $lifetime,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                session_start();
            }
            unset($user_in_db['password']);
            $_SESSION['user'] = $user_in_db;
            showMsg('success', 'Welcome ' . $user_in_db['name'], $return_url);
        } else {
            showMsg('warning', 'Password Didn\'t Match!', App::currentPath(true, true));
        }
    } else {
        showMsg('error', 'User Not Found!', App::currentPath(true, true));
    }
} else {
    showMsg('error', 'Paramters Not Found');
}
