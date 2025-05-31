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
?>

    <div class="p-2">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
            <h1 class="text-3xl font-bold text-textPrimary">All Users</h1>
            <div class="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-2 mt-2 md:mt-0">
                <!-- Search Form -->
                <form method="GET" action="<?= App::currentPath() ?>" class="relative">
                    <?php if ($entries['show_all']): ?>
                        <input type="hidden" name="show_all" value="1">
                    <?php endif; ?>
                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($entries['filters']['name'] ?? '') ?>"
                        placeholder="Search Name..."
                        class="pl-10 pr-2 py-2 border border-textSecondary/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:!ring-accent transition w-52">
                    <i class="ri-search-2-line text-lg text-textSecondary/80 absolute left-3 top-1.5"></i>
                </form>

                <!-- Reset Filters Link -->
                <?php if (!empty($_GET) && !in_array($_GET[0] ?? '', ['page', 'export'])): ?>
                    <a href="<?= App::currentPath() ?>" class="text-textSecondary hover:text-red-600 transition transform hover:rotate-90">
                        <i class="ri-loop-left-line text-red-600 text-2xl"></i>
                    </a>
                <?php endif; ?>

                <!-- Filter Button -->
                <div x-data="{ openFilters: false }" class="relative">
                    <button @click="openFilters = true" class="text-textSecondary hover:text-textPrimary/90 transition focus:outline-none">
                        <i class="ri-filter-line text-3xl"></i>
                    </button>
                    <div
                        x-show="openFilters"
                        x-cloak
                        @keydown.escape.window="openFilters = false"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-textPrimary/70 bg-opacity-50">
                        <div class="bg-bg w-full max-w-md rounded-lg shadow-lg overflow-y-auto">
                            <!-- Modal Header -->
                            <div class="flex justify-between items-center p-2 border border-textPrimary text-textPrimary">
                                <h3 class="text-xl font-semibold text-textPrimary">Apply Filters</h3>
                                <button @click="openFilters = false" class="text-textSecondary hover:text-textSecondary transition focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form method="GET" class="px-3 py-3 space-y-2">
                                <?php if ($entries['show_all']): ?>
                                    <input type="hidden" name="show_all" value="1">
                                <?php endif; ?>

                                <!-- Name Filter -->
                                <div>
                                    <label for="name" class="block mb-1 text-sm font-medium text-textSecondary">Name</label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        value="<?= htmlspecialchars($entries['filters']['name'] ?? '') ?>"
                                        placeholder="Enter name"
                                        class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:!ring-accent transition">
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
                                        class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:!ring-accent transition">
                                </div>

                                <!-- Role Filter -->
                                <div>
                                    <label for="role" class="block mb-1 text-sm font-medium text-textSecondary">Role</label>
                                    <select
                                        id="role"
                                        name="role"
                                        class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:!ring-accent transition">
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

                                <!-- Date Filter -->
                                <div>
                                    <label for="date" class="block mb-1 text-sm font-medium text-textSecondary">Date</label>
                                    <input
                                        type="date"
                                        id="date"
                                        name="date"
                                        value="<?= htmlspecialchars($entries['filters']['date'] ?? '') ?>"
                                        class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:!ring-accent transition">
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end space-x-4 pt-4 border-t border-textSecondary/20">
                                    <button
                                        type="button"
                                        @click="openFilters = false"
                                        class="px-4 py-2 text-sm font-medium !text-textPrimary !bg-textSecondary/10 rounded-lg hover:!bg-textSecondary/20 transition focus:outline-none">
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 text-sm font-medium !text-bg !bg-accent rounded-lg hover:!bg-accent/90 transition focus:outline-none">
                                        Apply
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <!-- Add Button -->
                <a href="<?= App::currentPath() . '?action=add' ?>" class="!bg-accent !text-bg rounded-lg hover:bg-accent/90 transition text-sm px-3 py-1 font-medium">
                    <i class="ri-add-line text-lg !text-bg mr-1"></i> Add
                </a>

                <!-- Export Button -->
                <a href="<?= $entries['toggle_url'] . '&action=export'; ?>" class="!bg-textPrimary !text-bg rounded-lg hover:!bg-textPrimary/90 transition text-sm px-3 py-1 font-medium">
                    <i class="ri-upload-2-line text-xl !text-bg"></i>
                </a>
            </div>
        </div>

        <!-- Info & Pagination -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 space-y-2 md:space-y-0">
            <!-- Info Text -->
            <div class="text-sm text-textPrimary">
                Showing <?= $entries['page']['from'] ?> to <?= $entries['page']['to'] ?> of <?= $entries['page']['total'] ?> entries
            </div>

            <!-- Show All & Entries Per Page & Pagination Links -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 space-y-2 sm:space-y-0">
                <!-- Show All Toggle -->
                <div class="flex items-center space-x-2">
                    <?php
                    $newShowAll = $entries['show_all'] ? null : '1';
                    if ($newShowAll === null) {
                        unset($entries['filters']['show_all']);
                    } else {
                        $entries['filters']['show_all'] = '1';
                    }
                    $toggleUrl = App::currentPath()
                        . (count($entries['filters']) ? ('?' . http_build_query($entries['filters'])) : '');
                    ?>
                    <input
                        type="checkbox"
                        id="showAllToggle"
                        class="form-checkbox h-5 w-5 text-accent transition duration-150"
                        <?= $entries['show_all'] ? 'checked' : '' ?>
                        onclick="window.location.href='<?= $toggleUrl ?>'">
                    <label for="showAllToggle" class="text-textPrimary text-sm font-medium">Show All</label>
                </div>
                <?php if (!$entries['show_all']): ?>
                    <form action="<?= App::currentPath() . '/action'; ?>" method="POST" class="flex items-center space-x-2">
                        <select
                            name="entries_per_page"
                            id="entries_per_page"
                            class="px-4 py-2 border border-textSecondary/30 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-accent"
                            onchange="this.form.submit()">
                            <?php
                            $EPP = DB::fetch('static_types', ['type_for' => 'entries_per_page'], 'all');
                            $user_p = App::currentUser()['preferences'];
                            $user_epp = !empty($user_p['entries_per_page']) ? $user_p['entries_per_page'] : '10';
                            foreach ($EPP as $s_epp) {
                                $slctd = $user_epp === $s_epp['value1'] ? 'selected' : '';
                                echo '<option value="' . $s_epp['value1'] . '" ' . $slctd . '>' . $s_epp['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </form>
                    <!-- Pagination Links -->
                    <div class="flex items-center space-x-2">
                        <?php if ($entries['pagination']['prev']): ?>
                            <a href="<?= $entries['pagination']['prev'] ?>" class="px-3 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-textSecondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <?php foreach ($entries['pagination']['links'] as $link): ?>
                            <?php if ($link['active']): ?>
                                <span class="px-3 py-2 bg-accent text-bg rounded border"><?= $link['page'] ?></span>
                            <?php else: ?>
                                <a href="<?= $link['url'] ?>" class="px-3 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                    <?= $link['page'] ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($entries['pagination']['next']): ?>
                            <a href="<?= $entries['pagination']['next'] ?>" class="px-3 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-textSecondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="text-textSecondary italic">All entries shown</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-bg rounded-lg border overflow-x-auto">
            <table class="min-w-full text-sm text-left text-textSecondary">
                <thead class="bg-textSecondary/10 text-xs uppercase text-textSecondary sticky top-0">
                    <tr>
                        <th class="p-2 border border-textPrimary text-textPrimary">No.</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Date</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Profile</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Name</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Username</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Role</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($entries['entries']) > 0): ?>
                        <?php foreach ($entries['entries'] as $index => $user): ?>
                            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='<?= App::currentPath() . '?action=view&id=' . $user['id'] ?>'">
                                <td class="p-2 border border-textPrimary text-textPrimary">#<?= ($entries['page']['from'] + $index) ?></td>
                                <td class="p-2 border border-textPrimary text-textPrimary"><?= date('d M, Y', strtotime($user['created_at'])) ?></td>
                                <td class="p-2 border border-textPrimary text-textPrimary">
                                    <img
                                        src="<?= Dir::getAsset(!empty($user['image']) ? 'uploads/' . $user['image'] : 'images/dummy-user-image.jpg') ?>"
                                        alt="Avatar"
                                        class="w-12 h-12 rounded-full object-cover border border-textSecondary/20">
                                </td>
                                <td class="p-2 border border-textPrimary text-textPrimary"><?= htmlspecialchars(ucwords($user['name'])) ?></td>
                                <td class="p-2 border border-textPrimary text-textPrimary">@<?= htmlspecialchars($user['username']) ?></td>
                                <td class="p-2 border border-textPrimary text-textPrimary">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $roleStyles[$user['role']]['bg'] ?> <?= $roleStyles[$user['role']]['textColor'] ?>">
                                        <?= $roleStyles[$user['role']]['text'] ?>
                                    </span>
                                </td>
                                <td class="p-2 border border-textPrimary text-textPrimary">
                                    <div class="flex items-center space-x-4">
                                        <?= App::icon('edit', '?id=' . $user['id']) ?>
                                        <?php if ($user['role'] !== 'superadmin'): ?>
                                            <?= App::icon('delete', '/action?task=user_delete&id=' . $user['id']) ?>
                                        <?php endif; ?>
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