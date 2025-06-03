<?php
// Base query
$sql = "SELECT * FROM branches";
$conditions = [];
if (!empty($_GET['name'])) {
    $name = strtolower(trim($_GET['name']));
    $conditions[] = "LOWER(name) LIKE '%" . addslashes($name) . "%'";
}
if (!empty($_GET['code'])) {
    $code = strtolower(trim($_GET['code']));
    $conditions[] = "LOWER(code) LIKE '%" . addslashes($code) . "%'";
}
if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $start = $date . ' 00:00:00';
        $end   = $date . ' 23:59:59';
        $conditions[] = "created_at BETWEEN '$start' AND '$end'";
    }
}
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$order_by = " ORDER BY created_at DESC";
if (($_GET['action'] ?? '') === 'export') {
    DB::loadEntries($sql, $order_by, $_GET);
} else {
    $entries = DB::loadEntries($sql, $order_by, $_GET);
    // $entries['entries'] = App::buildTree($entries['entries']);
    function pageFilters()
    {
?>
        <div>
            <label for="name" class="block mb-1 text-sm font-medium text-textSecondary">Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($entries['filters']['name'] ?? '') ?>"
                placeholder="Enter name"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
        <div>
            <label for="code" class="block mb-1 text-sm font-medium text-textSecondary">Code</label>
            <input
                type="text"
                id="code"
                name="code"
                value="<?= htmlspecialchars($entries['filters']['code'] ?? '') ?>"
                placeholder="Branch Code"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
        <div>
            <label for="currency" class="block mb-1 text-sm font-medium text-textSecondary">Currency</label>
            <select
                id="currency"
                name="currency"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
                <option value="">All Currencies</option>
                <?php
                $currencies = DB::fetch('static_types', ['type_for' => 'currency'], 'all');
                foreach ($currencies as $currency): ?>
                    <option
                        value="<?= $currency['value1']; ?>"
                        <?= (isset($entries['filters']['currency']) && $entries['filters']['currency'] === $currency['value1']) ? 'selected' : '' ?>>
                        <?= $currency['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="date" class="block mb-1 text-sm font-medium text-textSecondary">Date</label>
            <input
                type="date"
                id="date"
                name="date"
                value="<?= htmlspecialchars($entries['filters']['date'] ?? '') ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
    <?php
    }
    ?>

    <div class="p-2 relative" x-data="{ openFilters: false }">
        <?php App::pageTop($entries, 'pageFilters'); ?>
        <!-- Users Table -->
        <div class="bg-bg rounded-lg border overflow-x-auto">
            <table class="min-w-full text-sm text-left text-textSecondary">
                <thead class="bg-textSecondary/10 text-xs uppercase text-textSecondary sticky top-0">
                    <tr>
                        <th class="p-2 border border-textPrimary text-textPrimary">No.</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Date</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Code</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Name</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Currency</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Admin</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = DB::fetch('users');
                    $sortedU = App::sortByColumn($users, 'id', 'username');
                    if (count($entries['entries']) > 0) {
                        foreach ($entries['entries'] as $index => $branch) { ?>
                            <tr>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">#<?= ($entries['page']['from'] + $index); ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= ddate($branch['created_at']); ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= $branch['code']; ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-accent underline cursor-pointer"><a href="<?= App::currentPath() . '/view?id=' . $branch['id'] ?>"><?= htmlspecialchars(ucwords($branch['name'])) ?></a></td>

                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= $branch['currency']; ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">@<?= $sortedU[$branch['admin_id']] ?? ''; ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">
                                    <div class="flex items-center space-x-4">
                                        <?php
                                        if (in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
                                            if ($_SESSION['user']['role'] === 'superadmin') {
                                                echo App::icon('edit', App::currentPath() . '/manage?action=edit&id=' . $branch['id']);
                                                echo App::icon('delete', App::currentPath() . '/action?task=branch_delete&id=' . $branch['id']);
                                            } elseif ($_SESSION['user']['role'] === 'admin' && $_SESSION['user']['id'] == $branch['admin_id']) {
                                                echo App::icon('edit', App::currentPath() . '/manage?action=edit&id=' . $branch['id']);
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>