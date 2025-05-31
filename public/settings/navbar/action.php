<?php
if (isset($_POST['itemSubmit'])) {
    $data = [
        'parent_id' => ci($_POST['parent_id']),
        'label' => ci($_POST['label']),
        'slug' => ci($_POST['slug']),
        'position' => ci($_POST['position']),
        'is_view' => isset($_POST['is_view']) ? '1' : '0'
    ];
    if (!empty($_POST['id']) && $_POST['id'] > 0) {
        $result = DB::update('navbar', $data, ['id' => ci($_POST['id'])]);
        if ($result) {
            showMsg('success', 'Navbar Item Updated!', App::currentPath(true, true));
        } else {
            showMsg('error', '!Error: While Updating Navbar Item.', App::currentPath(true, true));
        }
    } else {
        $result = DB::insert('navbar', $data);
        if ($result) {
            showMsg('success', 'Navbar Item Added!', App::currentPath(true, true));
        } else {
            showMsg('error', '!Error: While Adding Navbar Item.', App::currentPath(true, true));
        }
    }
}
if (isset($_GET['task']) && $_GET['task'] === 'nav_delete') {
    $result = DB::delete('navbar', ['id' => ci($_GET['id'])]);
    if ($result) {
        showMsg('success', 'Menu Item Deleted!', App::currentPath(true, true));
    } else {
        showMsg('error', '!Error: While Deleting Menu Item.', App::currentPath(true, true));
    }
}
