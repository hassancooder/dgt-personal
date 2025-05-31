<?php
if (isset($_POST['entries_per_page'])) {
    $userId = $_SESSION['user']['id'];
    $user = DB::fetch('users', ['id' => $userId], 'one');
    if ($user) {
        $preferences = jd($user['preferences'] ?? '[]');
        $preferences['entries_per_page'] = ci($_POST['entries_per_page']);
        $updatedPreferences = json_encode($preferences);
        $update = DB::update('users', ['preferences' => $updatedPreferences], ['id' => $userId]);
        if ($update) {
            $_SESSION['user']['preferences'] = $preferences;
            showMsg('success', 'Entries Per Page saved successfully!', App::currentPath(true, true));
        } else {
            showMsg('error', 'Failed to update Entries Per Page.', App::currentPath(true, true));
        }
    } else {
        showMsg('error', 'User not found!', App::currentPath(true, true));
    }
}
