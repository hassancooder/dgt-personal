<?php

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $good = DB::fetch('goods', ['id' => $_GET['id']], 'one');
    $good['variations'] = jd($good['variations']);
} else {
    $good = ['variations' => []];
}
?>
<section class="p-3 max-w-5xl mx-auto space-y-3">
    <h2 class="text-2xl font-semibold text-textPrimary mb-4"><?= empty($good['name']) ? 'Add New' : 'Edit'; ?> Good</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <form method="POST" action="<?= App::parentPath() . '/action' ?>" class="space-y-4">
            <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">
            <div class="grid grid-cols-1 gap-3">
                <input type="text" name="name" placeholder="Name" value="<?= $good['name'] ?? ''; ?>"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
                <input type="text" name="origin" placeholder="Origin" value="<?= $good['origin'] ?? ''; ?>"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
                <input type="text" name="hs_code" placeholder="HS Code" value="<?= $good['hs_code'] ?? ''; ?>"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
            </div>
            <button type="submit" name="goodSubmit"
                class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
                Save
            </button>
        </form>

        <!-- Types and Sizes Display -->
        <div class="space-y-4">
            <div class="border rounded-lg overflow-hidden shadow-sm">
                <div class="bg-gray-100 px-4 py-2 font-medium text-textPrimary border-b">Variations</div>
                <div class="divide-y divide-gray-200">
                    <?php $types = $sizes = [];
                    if (!empty($good['variations'])): ?>
                        <?php foreach ($good['variations'] as $Tkey => $type):
                            $types[$Tkey] = $type; ?>
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-textPrimary"><?= htmlspecialchars($type['type']) ?></div>
                                    <div class="flex items-center space-x-2">
                                        <?php
                                        echo App::icon('edit', App::currentPath() . '?action=edit&id=' . $good['id'] . '&type_key=' . $Tkey);
                                        echo App::icon('delete', App::parentPath() . '/action?task=type_delete&good_id=' . $good['id'] . '&type_key=' . $Tkey);
                                        ?>
                                    </div>
                                </div>
                                <?php if (!empty($type['sizes'])): ?>
                                    <div class="mt-2 ml-4 space-y-1">
                                        <?php foreach ($type['sizes'] as $Skey => $size):
                                            $sizes[$Skey] = $size; ?>
                                            <div class="flex items-center justify-between text-sm text-gray-700 bg-gray-50 px-2 py-1 rounded hover:bg-gray-100">
                                                <span><?= htmlspecialchars($size); ?></span>
                                                <div class="flex items-center space-x-2">
                                                    <?php
                                                    echo App::icon('edit', App::currentPath() . '?action=edit&id=' . $good['id'] . '&type_key=' . $Tkey . '&size_key=' . $Skey);
                                                    echo App::icon('delete', App::parentPath() . '/action?task=size_delete&good_id=' . $good['id'] . '&type_key=' . $Tkey . '&size_key=' . $Skey);
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-gray-500">No variations added yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<hr class="my-3 border-t border-gray-300">
<section class="p-3 max-w-5xl mx-auto space-y-3">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

        <!-- Type Form -->
        <form method="POST" action="<?= App::parentPath() . '/action' ?>" class="space-y-4">
            <h2 class="text-2xl font-semibold text-textPrimary mb-4"><?= empty($good['name']) ? 'Add New' : 'Edit'; ?> Type</h2>
            <input type="hidden" name="good_id" value="<?= $good['id'] ?? '' ?>">

            <div class="grid grid-cols-1 gap-3">
                <!-- Select Existing Type (Optional for linkage or edit) -->
                <select name="existing_type_key"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
                    <option value="">Select Existing Type (optional)</option>
                    <?php foreach ($types as $key => $type): ?>
                        <option value="<?= $key ?>" <?= (($_GET['type_key'] ?? '') == $key ? 'selected' : '') ?>>
                            <?= htmlspecialchars($type['type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                $typeName = '';
                if (isset($_GET['type_key']) && isset($types[$_GET['type_key']])) {
                    $typeName = $types[$_GET['type_key']]['type'];
                }
                ?>
                <input type="text" name="name" placeholder="Type Name" value="<?= htmlspecialchars($typeName); ?>"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
            </div>
            <button type="submit" name="typeSubmit"
                class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
                Save
            </button>
        </form>

        <!-- Size Form -->
        <form method="POST" action="<?= App::parentPath() . '/action' ?>" class="space-y-4">
            <h2 class="text-2xl font-semibold text-textPrimary mb-4"><?= empty($good['name']) ? 'Add New' : 'Edit'; ?> Size</h2>
            <input type="hidden" name="size_key" value="<?= $_GET['size_key'] ?? '' ?>">
            <input type="hidden" name="good_id" value="<?= $good['id'] ?? '' ?>">

            <div class="grid grid-cols-1 gap-3">
                <!-- Select Type -->
                <select name="type_key" required
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
                    <option value="">Select Type</option>
                    <?php foreach ($types as $key => $type): ?>
                        <option value="<?= $key ?>" <?= (($_GET['type_key'] ?? '') == $key ? 'selected' : '') ?>>
                            <?= htmlspecialchars($type['type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Select Size -->
                <select name="size_key_combined"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
                    <option value="">Select Size (Type => Size)</option>
                    <?php foreach ($types as $typeKey => $type): ?>
                        <?php if (!empty($type['sizes'])): ?>
                            <?php foreach ($type['sizes'] as $sizeKey => $size): ?>
                                <option value="<?= $typeKey . '|' . $sizeKey ?>">
                                    <?= htmlspecialchars($type['type'] . ' => ' . $size) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php
                $sizeName = '';
                if (isset($_GET['type_key'], $_GET['size_key']) && isset($types[$_GET['type_key']]['sizes'][$_GET['size_key']])) {
                    $sizeName = $types[$_GET['type_key']]['sizes'][$_GET['size_key']];
                }
                ?>
                <input type="text" name="name" placeholder="New Size Name" value="<?= htmlspecialchars($sizeName); ?>"
                    class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
            </div>
            <button type="submit" name="sizeSubmit"
                class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
                Save
            </button>
        </form>
    </div>
</section>