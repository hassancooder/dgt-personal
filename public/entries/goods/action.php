<?php
if (isset($_POST['goodSubmit'])) {
    $data = [
        'name' => ci($_POST['name']),
        'origin' => ci($_POST['origin']),
        'hs_code' => ci($_POST['hs_code'])
    ];
    $result = !empty($_POST['id'])
        ? DB::update('goods', $data, ['id' => $_POST['id']])
        : DB::insert('goods', $data);
    if ($result) {
        showMsg('success', 'Good saved!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed to save good!', App::currentPath(true, true));
    }
}
if (isset($_GET['task']) && $_GET['task'] === 'good_delete') {
    $result = DB::delete('goods', ['id' => $_GET['id']]);
    if ($result) {
        showMsg('success', 'Success!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Failed!', App::currentPath(true, true));
    }
}
// Save or Update Type
if (isset($_POST['typeSubmit'])) {
    $goodId = $_POST['good_id'] ?? null;
    $typeKey = !empty($_POST['existing_type_key']) ? $_POST['existing_type_key'] : null;
    $typeName = ci($_POST['name']);
    $good = DB::fetch('goods', ['id' => $goodId], 'one');
    $variations = jd($good['variations']);
    if (!is_array($variations)) $variations = [];
    if ($typeKey !== null && isset($variations[$typeKey])) {
        $variations[$typeKey]['type'] = $typeName;
    } else {
        $variations[$typeName . '_' . $goodId] = ['type' => $typeName, 'sizes' => []];
    }
    $result = DB::update('goods', ['variations' => je($variations)], ['id' => $goodId]);
    if ($result) {
        showMsg('success', 'Type saved!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Type save failed!', App::currentPath(true, true));
    }
}
if (isset($_POST['sizeSubmit'])) {
    $goodId = $_POST['good_id'] ?? null;
    $typeKey = !empty($_POST['existing_type_key']) ? $_POST['existing_type_key'] : null;
    $sizeKey = !empty($_POST['existing_size_key']) ? $_POST['existing_size_key'] : null;
    $sizeName = ci($_POST['name']);

    $good = DB::fetch('goods', ['id' => $goodId], 'one');
    $variations = jd($good['variations']);

    if (!isset($variations[$typeKey]['sizes']) || !is_array($variations[$typeKey]['sizes'])) {
        $variations[$typeKey]['sizes'] = [];
    }

    if ($sizeKey !== null && isset($variations[$typeKey]['sizes'][$sizeKey])) {
        $variations[$typeKey]['sizes'][$sizeKey] = $sizeName;
    } else {
        $variations[$typeKey]['sizes'][$sizeName . '_' . $goodId] = $sizeName;
    }
    $result = DB::update('goods', ['variations' => je($variations)], ['id' => $goodId]);
    if ($result) {
        showMsg('success', 'Size saved!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Size save failed!', App::currentPath(true, true));
    }
}


// Delete Type
if (isset($_GET['task']) && $_GET['task'] === 'type_delete') {
    $goodId = $_POST['good_id'] ?? null;
    $typeKey = $_POST['type_key'] ?? null;

    $good = DB::fetch('goods', ['id' => $goodId], 'one');
    $variations = jd($good['variations']);

    if (isset($variations[$typeKey])) {
        unset($variations[$typeKey]);
    }
    $result = DB::update('goods', ['variations' => je($variations)], ['id' => $goodId]);
    if ($result) {
        showMsg('success', 'Type deleted!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Type delete failed!', App::currentPath(true, true));
    }
}

if (isset($_GET['task']) && $_GET['task'] === 'size_delete') {
    $goodId = $_POST['good_id'] ?? null;
    $typeKey = $_POST['type_key'] ?? null;
    $sizeKey = $_POST['size_key'] ?? null;

    $good = DB::fetch('goods', ['id' => $goodId], 'one');
    $variations = jd($good['variations']);

    if (isset($variations[$typeKey]['sizes'][$sizeKey])) {
        unset($variations[$typeKey]['sizes'][$sizeKey]);
    }
    $result = DB::update('goods', ['variations' => je($variations)], ['id' => $goodId]);
    if ($result) {
        showMsg('success', 'Size deleted!', App::currentPath(true, true));
    } else {
        showMsg('error', 'Size delete failed!', App::currentPath(true, true));
    }
}
