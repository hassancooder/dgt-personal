<?php
// Base query
$sql = "SELECT * FROM users";
$conditions = [];
if (!empty($_GET['username'])) {
    $username = strtolower(trim($_GET['username']));
    $conditions[] = "LOWER(username) LIKE '%" . addslashes($username) . "%'";
}
if (!empty($_GET['name'])) {
    $name = strtolower(trim($_GET['name']));
    $conditions[] = "LOWER(name) LIKE '%" . addslashes($name) . "%'";
}
if (!empty($_GET['branch_id'])) {
    $branch_id = strtolower(trim($_GET['branch_id']));
    $conditions[] = "LOWER(branch_id) LIKE '%" . addslashes($branch_id) . "%'";
}
if (!empty($_GET['role'])) {
    $role = strtolower(trim($_GET['role']));
    $conditions[] = "LOWER(role) = '" . addslashes($role) . "'";
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
    function pageFilters()
    {
?>
        <!-- Name Filter -->
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

        <!-- Username Filter -->
        <div>
            <label for="username" class="block mb-1 text-sm font-medium text-textSecondary">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($entries['filters']['username'] ?? '') ?>"
                placeholder="Enter username"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>

        <!-- Role Filter -->
        <div>
            <label for="role" class="block mb-1 text-sm font-medium text-textSecondary">Role</label>
            <select
                id="role"
                name="role"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
                <option value="">All Roles</option>
                <?php
                $roles = DB::fetch('static_types', ['type_for' => 'role'], 'all');
                foreach ($roles as $role): ?>
                    <option
                        value="<?= $role['value1']; ?>"
                        <?= (isset($entries['filters']['role']) && $entries['filters']['role'] === $role['value1']) ? 'selected' : '' ?>>
                        <?= $role['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="branch_id" class="block mb-1 text-sm font-medium text-textSecondary">Branch</label>
            <select
                id="branch_id"
                name="branch_id"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
                <option value="">All Branches</option>
                <?php
                $branches = DB::fetch('branches');
                foreach ($branches as $branch): ?>
                    <option
                        value="<?= $branch['id']; ?>"
                        <?= (isset($entries['filters']['branch_id']) && $entries['filters']['branch_id'] === $branch['id']) ? 'selected' : '' ?>>
                        <?= $branch['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Date Filter -->
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
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">No.</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Date</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Profile</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Name</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Username</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Role</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Branch</th>
                        <th class="p-2 border text-nowrap border-textPrimary text-textPrimary">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $branches = DB::fetch('branches');
                    $sortedB = App::sortByColumn($branches, 'id', 'name');
                    if (count($entries['entries']) > 0): ?>
                        <?php foreach ($entries['entries'] as $index => $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">#<?= ($entries['page']['from'] + $index); ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= date('d M, Y', strtotime($user['created_at'])) ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">
                                    <img
                                        src="<?= Dir::getAsset(!empty($user['image']) ? 'uploads/' . $user['image'] : 'images/dummy-user-image.jpg') ?>"
                                        alt="Avatar"
                                        class="w-9 h-9 rounded-full object-cover border border-textSecondary/20">
                                </td>
                                <td class="p-2 border text-nowrap border-textPrimary text-accent underline cursor-pointer"><a href="<?= App::currentPath() . '/view?id=' . $user['id'] ?>"><?= htmlspecialchars(ucwords($user['name'])) ?></a></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">@<?= htmlspecialchars($user['username']) ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $roleStyles[$user['role']]['bg'] ?> <?= $roleStyles[$user['role']]['textColor'] ?>">
                                        <?= $roleStyles[$user['role']]['text'] ?>
                                    </span>
                                </td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= htmlspecialchars($sortedB[$user['branch_id']] ?? '') ?></td>

                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">
                                    <div class="flex items-center space-x-4">
                                        <?php
                                        if ($_SESSION['user']['branch_id'] === $user['branch_id'] || $_SESSION['user']['role'] === 'superadmin') {
                                            echo App::icon('edit', App::currentPath() . '/manage?action=edit&id=' . $user['id']);
                                            if ($user['role'] !== 'superadmin') {
                                                echo App::icon('delete', App::currentPath() . '/action?task=user_delete&id=' . $user['id']);
                                                if ((int)$user['is_active']) {
                                                    echo App::icon('unlock', App::currentPath() . '/action?task=user_lock&id=' . $user['id']);
                                                } else {
                                                    echo App::icon('lock', App::currentPath() . '/action?task=user_unlock&id=' . $user['id']);
                                                }
                                            }
                                        } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-textSecondary">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php } ?>