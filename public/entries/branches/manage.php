<?php

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $branch = DB::fetch('branches', ['id' => $_GET['id']], 'one');
    $branch['details'] = jd($branch['details']);
} else {
    $branch = ['details' => []];
}
?>
<section class="p-3 max-w-5xl mx-auto space-y-3">
    <h2 class="text-2xl font-semibold text-textPrimary mb-4"><?= empty($branch['name']) ? 'Add New' : 'Edit'; ?> Branch</h2>
    <form method="POST" action="<?= App::parentPath() . '/action' ?>" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="name" placeholder="Name" value="<?= $branch['name'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>

            <input type="text" name="code" placeholder="code" value="<?= $branch['code'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>

            <select name="parent_id" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="0" selected>No Parent</option>
                <?php $branches = DB::fetch('branches', [], 'all');
                foreach ($branches as $my_branch) {
                    $slctd = $my_branch['id'] == ($branch['parent_id'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $my_branch['id'] . '"' . $slctd . '>' . $my_branch['name'] . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <select name="admin_id" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select Admin</option>
                <?php $users = DB::fetch('users', ['role' => ['admin', 'superadmin']], 'all');
                foreach ($users as $user) {
                    $slctd = $user['id'] == ($branch['admin_id'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $user['id'] . '"' . $slctd . '>' . $user['name'] . ' | @' . $user['username'] . ' </option>';
                }
                ?>
            </select>

            <select name="currency" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select Currency</option>
                <?php $currecies = DB::fetch('static_types', ['type_for' => 'currency'], 'all');
                foreach ($currecies as $currency) {
                    $slctd = $currency['value1'] == ($branch['currency'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $currency['value1'] . '"' . $slctd . '>' . $currency['name'] . '</option>';
                }
                ?>
            </select>
        </div>
        <button type="submit" name="branchSubmit"
            class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
            Save
        </button>
    </form>
</section>
<hr class="my-3 border-t border-gray-300">
<section class="p-3 max-w-5xl mx-auto space-y-3">
    <form method="POST" action="<?= App::parentPath() . '/action' ?>" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="admin_name" placeholder="Admin Name" value="<?= $branch['admin_name'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>
            <input type="text" name="father_name" placeholder="Father's Name" value="<?= $branch['father_name'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>

            <select name="country" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select Country</option>
                <?php $countries = DB::fetch('static_types', ['type_for' => 'country'], 'all');
                foreach ($countries as $country) {
                    $slctd = $country['value1'] == (!empty($branch['details']) ? $branch['details']['country'] : '') ? 'selected' : '';
                    echo '<option value="' . $country['value1'] . '"' . $slctd . '>' . $country['name'] . '</option>';
                }
                ?>
            </select>
            <select name="state" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select State</option>
                <?php $states = DB::fetch('static_types', ['type_for' => 'state'], 'all');
                foreach ($states as $state) {
                    $slctd = $state['value1'] == (!empty($branch['details']) ? $branch['details']['state'] : '') ? 'selected' : '';
                    echo '<option value="' . $state['value1'] . '"' . $slctd . '>' . $state['name'] . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <select name="city" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select City</option>
                <?php $cities = DB::fetch('static_types', ['type_for' => 'city'], 'all');
                foreach ($cities as $city) {
                    $slctd = $city['value1'] == (!empty($branch['details']) ? $branch['details']['city'] : '') ? 'selected' : '';
                    echo '<option value="' . $city['value1'] . '"' . $slctd . '>' . $city['name'] . '</option>';
                }
                ?>
            </select>
            <input type="number" name="phone" placeholder="Phone" value="<?= $branch['details']['phone'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="number" name="whatsapp" placeholder="WhatsApp" value="<?= $branch['details']['whatsapp'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="text" name="postal_code" placeholder="Email Address" value="<?= $branch['details']['postal_code'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="email" name="email" placeholder="Postal Code" value="<?= $branch['details']['email'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>
        <textarea name="address" rows="2" placeholder="Address"
            class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition resize-none"><?= $branch['details']['address'] ?? ''; ?></textarea>
        <button type="submit" name="detailsSubmit"
            class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
            Save
        </button>
    </form>
</section>