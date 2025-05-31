<?php
$allItems = DB::con()->fetch('navbar', [], 'all');
$menuTree = buildTreeWithPaths($allItems);
$navItem = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    foreach ($allItems as $item) {
        if ($item['id'] == $id) {
            $navItem = $item;
            break;
        }
    }
}
?>

<div class="flex flex-col lg:flex-row gap-6 p-6">
    <!-- Left: Form -->
    <div class="lg:w-1/2 w-full bg-bg p-6 rounded-xl border border-textSecondary">
        <h2 class="text-xl font-semibold text-Primary mb-6">
            <?= $navItem ? "Edit Menu Item" : "Add New Menu Item" ?>
        </h2>

        <form action="<?= App::currentPath() . '/action'; ?>" method="POST" class="space-y-5">
            <input type="hidden" name="id" value="<?= $navItem['id'] ?? '' ?>">

            <!-- Label & Slug -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="label" class="block text-sm font-medium text-textSecondary mb-1">Label</label>
                    <input type="text" name="label" id="label"
                        value="<?= htmlspecialchars($navItem['label'] ?? '') ?>"
                        class="w-full border !text-textPrimary !bg-bg border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                        required>
                </div>
                <div class="flex-1">
                    <label for="slug" class="block text-sm font-medium text-textSecondary mb-1">Slug</label>
                    <input type="text" name="slug" id="slug"
                        value="<?= htmlspecialchars($navItem['slug'] ?? '') ?>"
                        class="w-full border !text-textPrimary !bg-bg border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                        required>
                </div>
            </div>

            <!-- Position & Parent -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="position" class="block text-sm font-medium text-textSecondary mb-1">Position</label>
                    <input type="number" name="position" id="position"
                        value="<?= htmlspecialchars($navItem['position'] ?? '') ?>"
                        class="w-full border !text-textPrimary !bg-bg border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                        min="1" required>
                </div>
                <div class="flex-1">
                    <label for="parent_id" class="block text-sm font-medium text-textSecondary mb-1">Parent</label>
                    <select name="parent_id" id="parent_id"
                        class="w-full border !text-textPrimary !bg-bg border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">-- No Parent --</option>
                        <?php foreach ($allItems as $item): ?>
                            <?php if ($navItem && $item['id'] == $navItem['id']) continue; ?>
                            <option value="<?= $item['id'] ?>" <?= ($navItem && $navItem['parent_id'] == $item['id']) ? 'selected' : '' ?>>
                                <?= ucfirst($item['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Is View & Submit -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                <label class="inline-flex items-center text-sm text-textSecondary">
                    <input type="checkbox" name="is_view" id="is_view" value="1"
                        class="accent-bg"
                        <?= ($navItem && isset($navItem['is_view']) && $navItem['is_view'] === '1') ? 'checked' : '' ?>>
                    <span class="ml-2">Is View</span>
                </label>

                <button type="submit" name="itemSubmit"
                    class="!bg-accent hover:!bg-accent/90 !text-bg text-sm font-medium px-5 py-2 rounded-md transition">
                    <?= $navItem ? "Update Item" : "Add Item" ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Right: Menu Preview -->
    <div class="lg:w-1/2 w-full overflow-auto max-h-[80vh] bg-bg p-6 rounded-xl border border-textSecondary">
        <?php renderMenu($menuTree, App::currentPath(true), true); ?>
    </div>
</div>