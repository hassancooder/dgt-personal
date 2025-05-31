<?php
// Type Submit
if (isset($_POST['typeSubmit'])) {
    $data = [
        'type_for' => ci($_POST['type_for']),
        'name' => ci($_POST['name']),
        'value1' => ci($_POST['value1'])
    ];
    if (!empty($_POST['id']) && $_POST['id'] > 0) {
        $result = DB::update('static_types', $data, ['id' => ci($_POST['id'])]);
        if ($result) {
            showMsg('success', 'Static Type Updated!', App::currentPath(true, true));
        } else {
            showMsg('error', '!Error: While Updating Static Type.', App::currentPath(true, true));
        }
    } else {
        $result = DB::insert('static_types', $data);
        if ($result) {
            showMsg('success', 'Static Type Added!', App::currentPath(true, true));
        } else {
            showMsg('error', '!Error: While Adding Static Type.', App::currentPath(true, true));
        }
    }
}
if (isset($_GET['task']) && $_GET['task'] === 'type_delete') {
    $result = DB::delete('static_types', ['id' => ci($_GET['id'])]);
    if ($result) {
        showMsg('success', 'Static Type Deleted!', App::currentPath(true, true));
    } else {
        showMsg('error', '!Error: While Deleting Static Type.', App::currentPath(true, true));
    }
}
