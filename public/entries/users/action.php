<?php
if (isset($_POST['userSubmit'])) {

    $data = [
        'name' => ci($_POST['name']),
        'username' => ci($_POST['username']),
        'role' => ci($_POST['role']),
        'branch_id' => ci($_POST['branch_id']),
        'allowed_routes' => je(explode(',', $_POST['allowed_routes'])),
    ];
    if (!empty($_POST['password'])) {
        $data['password'] = App::encrypt($_POST['password']);
    }
    if (!empty($_FILES['profile_image']['name'])) {
        $data['image'] = dir::uploadFiles('assets/uploads', $_FILES['profile_image'])[0];
    }
    $result = !empty($_POST['id']) ? DB::update('users', $data, ['id' => $_POST['id']]) : DB::insert('users', $data);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
if (isset($_GET['task']) && ($_GET['task'] === 'user_lock' || $_GET['task'] === 'user_unlock')) {
    $result = DB::update('users', ['is_active' => ($_GET['task'] === 'user_lock' ? 0 : 1)], ['id' => $_GET['id']]);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
if (isset($_GET['task']) && $_GET['task'] === 'user_delete') {
    $result = DB::delete('users', ['id' => $_GET['id']]);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
if (isset($_POST['personalSubmit'])) {
    $data = [
        'details' => [
            'father_name' => ci($_POST['father_name']),
            'gender' => ci($_POST['gender']),
            'id_type' => ci($_POST['id_type']),
            'id_number' => ci($_POST['id_number']),
            'id_reg_date' => ci($_POST['id_reg_date']),
            'id_exp_date' => ci($_POST['id_exp_date']),
            'country' => ci($_POST['country']),
            'state' => ci($_POST['state']),
            'city' => ci($_POST['city']),
            'phone' => ci($_POST['phone']),
            'whatsapp' => ci($_POST['whatsapp']),
            'postal_code' => ci($_POST['postal_code']),
            'email' => ci($_POST['email']),
            'account_no' => ci($_POST['account_no']),
            'account_id' => ci($_POST['account_id']),
            'address' => ci($_POST['address'])
        ]
    ];
    $user = DB::fetch('users', ['id' => $_POST['id']], 'one');
    if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
        $user = DB::fetch('users', ['id' => $_POST['id']], 'one');
        $previous_details = isset($user['details']) ? jd($user['details']) : [];
        if (!is_array($previous_details)) $previous_details = [];
        $data['details'] = je([...$previous_details, ...$data['details']]);
        $result = DB::update('users', $data, ['id' => $_POST['id']]);
    } else {
        $result = DB::insert('users', $data);
    }

    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
