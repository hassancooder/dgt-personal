<?php
// Base query
$sql = "SELECT * FROM goods";
$conditions = [];

if (!empty($_GET['name'])) {
    $name = strtolower(trim($_GET['name']));
    $conditions[] = "LOWER(name) LIKE '%" . addslashes($name) . "%'";
}

if (!empty($_GET['origin'])) {
    $origin = strtolower(trim($_GET['origin']));
    $conditions[] = "LOWER(origin) LIKE '%" . addslashes($origin) . "%'";
}

if (!empty($_GET['hs_code'])) {
    $hs_code = strtolower(trim($_GET['hs_code']));
    $conditions[] = "LOWER(hs_code) LIKE '%" . addslashes($hs_code) . "%'";
}

// ✅ Type filter using JSON_CONTAINS
if (!empty($_GET['type'])) {
    $type = trim($_GET['type']);
    $conditions[] = "JSON_SEARCH(variations, 'one', '$type', NULL, '$[*].type') IS NOT NULL";
}

// ✅ Size filter using JSON_SEARCH on sizes array
if (!empty($_GET['size'])) {
    $size = trim($_GET['size']);
    $conditions[] = "JSON_SEARCH(variations, 'one', '$size', NULL, '$[*].sizes[*]') IS NOT NULL";
}

// ✅ Date filter
if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $start = $date . ' 00:00:00';
        $end   = $date . ' 23:59:59';
        $conditions[] = "created_at BETWEEN '$start' AND '$end'";
    }
}

// Combine all conditions
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
            <label for="origin" class="block mb-1 text-sm font-medium text-textSecondary">Origin</label>
            <input
                type="text"
                id="origin"
                name="origin"
                value="<?= htmlspecialchars($entries['filters']['origin'] ?? '') ?>"
                placeholder="Enter origin"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
        <div>
            <label for="hs_code" class="block mb-1 text-sm font-medium text-textSecondary">Hs Code</label>
            <input
                type="text"
                id="hs_code"
                name="hs_code"
                value="<?= htmlspecialchars($entries['filters']['hs_code'] ?? '') ?>"
                placeholder="Enter hs_code"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
        <div>
            <label for="type" class="block mb-1 text-sm font-medium text-textSecondary">Type</label>
            <input
                type="text"
                id="type"
                name="type"
                value="<?= htmlspecialchars($entries['filters']['type'] ?? '') ?>"
                placeholder="Enter type"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
        </div>
        <div>
            <label for="size" class="block mb-1 text-sm font-medium text-textSecondary">Size</label>
            <input
                type="text"
                id="size"
                name="size"
                value="<?= htmlspecialchars($entries['filters']['size'] ?? '') ?>"
                placeholder="Enter size"
                class="w-full border border-textSecondary/30 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition">
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
                        <th class="p-2 border border-textPrimary text-textPrimary">Name</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Origin</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">HS Code</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Variations</th>
                        <th class="p-2 border border-textPrimary text-textPrimary">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($entries['entries']) > 0) {
                        foreach ($entries['entries'] as $index => $good) { ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">#<?= ($entries['page']['from'] + $index); ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= ddate($good['created_at']); ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-accent underline cursor-pointer"><a href="<?= App::currentPath() . '/view?id=' . $good['id'] ?>"><?= htmlspecialchars(ucwords($good['name'])) ?></a></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= $good['origin']; ?></td>
                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary"><?= $good['hs_code']; ?></td>
                                <td class="p-2 border text-textPrimary text-left align-top whitespace-normal border-textPrimary">
                                    <?php
                                    $variations = json_decode($good['variations'], true);
                                    $typeCount = 0;
                                    $sizeCount = 0;
                                    $totalVariations = 0;

                                    if (is_array($variations) && count($variations)) {
                                        foreach (array_values($variations) as $i => $variation) {
                                            $typeCount++;
                                            $sizes = $variation['sizes'] ?? [];
                                            $scount = is_array($sizes) ? count($sizes) : 0;
                                            $sizeCount += $scount;
                                            $totalVariations += $scount;

                                            // Safe echo
                                            $typeLabel = 'Type ' . ($i + 1);
                                            $typeName = htmlspecialchars($variation['type'] ?? 'Unknown');
                                            echo "$typeLabel: $typeName, $scount sizes<br>";
                                        }
                                        echo "Total Types: $typeCount, Total Sizes: $sizeCount<br>";
                                        echo "Total Variations: $totalVariations";
                                    } else {
                                        echo "No variations";
                                    }
                                    ?>

                                </td>

                                <td class="p-2 border text-nowrap border-textPrimary text-textPrimary">
                                    <div class="flex items-center space-x-4">
                                        <?php
                                        if (in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
                                            if ($_SESSION['user']['role'] === 'superadmin') {
                                                echo App::icon('edit', App::currentPath() . '/manage?action=edit&id=' . $good['id']);
                                                echo App::icon('delete', App::currentPath() . '/action?task=branch_delete&id=' . $good['id']);
                                            } elseif ($_SESSION['user']['role'] === 'admin' && $_SESSION['user']['id'] == $good['admin_id']) {
                                                echo App::icon('edit', App::currentPath() . '/manage?action=edit&id=' . $good['id']);
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
    <?php } ?>