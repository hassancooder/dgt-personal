<?php
$allTypes = DB::con()->fetch('static_types', [], 'all');
$currentTypeId = isset($_GET['id']) ? intval($_GET['id']) : null;
$typeItem = null;
if ($currentTypeId) {
    foreach ($allTypes as $type) {
        if ($type['id'] == $currentTypeId) {
            $typeItem = $type;
            break;
        }
    }
}
?>

<div class="flex gap-6 px-3 pt-2">
    <!-- Left: Type Form -->
    <div class="w-1/2 bg-bg px-4 pt-2 rounded-xl border border-textSecondary/20">
        <h2 class="text-lg font-semibold text-textPrimary mb-4">
            <?= $typeItem ? "Edit Type" : "Create New Type" ?>
        </h2>
        <form action="<?= App::currentPath() . '/action'; ?>" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $typeItem['id'] ?? '' ?>">

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-textPrimary mb-1">Name</label>
                <input type="text" id="name" name="name"
                    value="<?= ci($typeItem['name'] ?? '') ?>"
                    class="w-full border !text-textPrimary !bg-bg border-textSecondary/20 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:!ring-accent"
                    placeholder="Enter Name" required>
            </div>

            <!-- Value -->
            <div>
                <label for="value1" class="block text-sm font-medium text-textPrimary mb-1">Value</label>
                <input type="text" id="value1" name="value1"
                    value="<?= ci($typeItem['value1'] ?? '') ?>"
                    class="w-full border !text-textPrimary !bg-bg border-textSecondary/20 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:!ring-accent"
                    placeholder="Enter Value" required>
            </div>

            <!-- Type For -->
            <div>
                <label for="type_for" class="block text-sm font-medium text-textPrimary mb-1">Type For</label>
                <select id="type_for" name="type_for"
                    class="w-full border !text-textPrimary !bg-bg border-textSecondary/20 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:!ring-accent">
                    <option value="">New Type</option>
                    <?php foreach ($allTypes as $type):
                        if ($type['id'] == ($typeItem['id'] ?? null)) continue;
                        $slctd = ($type['value1'] ?? '') == ($typeItem['type_for'] ?? '') ? 'selected' : '';
                    ?>
                        <option value="<?= $type['value1'] ?? ''; ?>" <?= $slctd; ?>><?= $type['name']; ?></option>
                    <?php endforeach; ?>
                </select>


            </div>

            <!-- Submit Button -->
            <button type="submit" name="typeSubmit"
                class="w-full !bg-accent hover:!bg-accent/90 !text-bg font-medium py-2 px-4 rounded-lg text-sm transition">
                <?= $typeItem ? "Update Type" : "Add Type" ?>
            </button>
        </form>
    </div>

    <!-- Right: Type List -->
    <div class="w-1/2 max-h-[80vh] overflow-auto bg-bg px-4 pt-2 rounded-xl border border-textSecondary/20">
        <h3 class="text-md font-semibold text-textPrimary mb-4">All Types</h3>
        <div class="space-y-3">
            <?php foreach ($allTypes as $type):
                $type['type_for'] = empty($type['type_for']) ? 'Main' : $type['type_for'];  ?>
                <div class="border border-textSecondary/10 rounded-md p-3 flex justify-between items-center">
                    <div>
                        <div class="text-sm text-textPrimary font-medium">
                            <?= ucfirst($type['name']) ?> (<?= ucfirst($type['type_for']); ?>)
                        </div>
                        <div class="text-xs text-textSecondary"><?= $type['value1'] ?></div>
                    </div>
                    <div class="flex gap-3 items-center">
                        <?php
                        echo App::icon('edit', '?id=' . $type['id']);
                        if ($type['value1'] !== 'superadmin') {
                            echo App::icon('delete', '/action?task=type_delete&id=' . $type['id']);
                        } ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($allTypes)): ?>
                <p class="text-sm text-textSecondary italic">No types found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>