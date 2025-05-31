<?php
/// Theme Submit
if (isset($_POST['themeSubmit'])) {
    $userId = $_SESSION['user']['id'];
    $user = DB::fetch('users', ['id' => $userId], 'one');
    if ($user) {
        $preferences = jd($user['preferences'] ?? '[]');
        $preferences['color_theme'] = [
            'bg' => $_POST['bgColor'],
            'accent' => $_POST['accentColor'],
            'textPrimary' => $_POST['textPrimary'],
            'textSecondary' => $_POST['textSecondary']
        ];
        $updatedPreferences = json_encode($preferences);
        $update = DB::update('users', ['preferences' => $updatedPreferences], ['id' => $userId]);
        if ($update) {
            $_SESSION['user']['preferences'] = $preferences;
            showMsg('success', 'Color theme saved successfully!', App::currentPath(true, true));
        } else {
            showMsg('error', 'Failed to update color theme.', App::currentPath(true, true));
        }
    } else {
        showMsg('error', 'User not found!', App::currentPath(true, true));
    }
}
