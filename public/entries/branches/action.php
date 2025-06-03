<?php
if (isset($_POST['branchSubmit'])) {

    $data = [
        'name' => ci($_POST['name']),
        'code' => ci($_POST['code']),
        'parent_id' => ci($_POST['parent_id']),
        'admin_id' => ci($_POST['admin_id']),
        'currency' => ci($_POST['currency']),
    ];
    $result = !empty($_POST['id']) ? DB::update('branches', $data, ['id' => $_POST['id']]) : DB::insert('branches', $data);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
if (isset($_GET['task']) && $_GET['task'] === 'branch_delete') {
    $result = DB::delete('branches', ['id' => $_GET['id']]);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
if (isset($_POST['detailsSubmit'])) {
    $data = [
        'details' => [
            'admin_name' => ci($_POST['admin_name']),
            'father_name' => ci($_POST['father_name']),
            'country' => ci($_POST['country']),
            'state' => ci($_POST['state']),
            'city' => ci($_POST['city']),
            'phone' => ci($_POST['phone']),
            'whatsapp' => ci($_POST['whatsapp']),
            'postal_code' => ci($_POST['postal_code']),
            'email' => ci($_POST['email']),
            'address' => ci($_POST['address'])
        ]
    ];
    $user = DB::fetch('branches', ['id' => $_POST['id']], 'one');
    if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
        $user = DB::fetch('branches', ['id' => $_POST['id']], 'one');
        $previous_details = isset($user['details']) ? jd($user['details']) : [];
        if (!is_array($previous_details)) $previous_details = [];
        $data['details'] = je([...$previous_details, ...$data['details']]);
        $result = DB::update('branches', $data, ['id' => $_POST['id']]);
    } else {
        $result = DB::insert('branches', $data);
    }

    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
