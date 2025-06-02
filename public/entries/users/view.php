<?php
if (isset($_GET['id'])) {
    $user = DB::fetch('users', ['id' => $_GET['id']], 'one');
    $user['allowed_routes'] = jd($user['allowed_routes'] ?? '[]');
    $user['details'] = jd($user['details'] ?? '[]');
    $user['preferences'] = jd($user['preferences'] ?? '[]');
} else {
    $user = ['allowed_routes' => [], 'details' => [], 'preferences' => []];
}
$routes = DB::fetch('navbar', [], 'all');
$default_img = 'https://placehold.co/100/FFFFFF/000?text=NO\nIMAGE';
?>
<section class="max-w-5xl mx-auto p-2">
    <div class="flex gap-2 items-center justify-between">
        <h2 class="text-3xl font-bold text-textPrimary mb-6">View User Profile</h2>
        <div>
            <a href="<?= ''; ?>" class="!bg-textPrimary mx-1 !text-bg rounded-lg hover:!bg-textPrimary/90 transition text-sm px-2 py-2 font-medium">
                <i class="ri-upload-2-line text-xl !text-bg"></i>
            </a>
        </div>
    </div>
    <div class="bg-bg border-2 border-textSecondary rounded-lg p-4 mb-4">
        <!-- Flex on large screens, stack on small screens -->
        <div class="flex flex-col md:flex-row gap-2 items-start">

            <!-- Left: Image + Name/Role (40%) -->
            <div class="w-full md:w-2/5 flex items-center gap-4">
                <!-- Profile Image -->
                <div class="w-28 h-28 rounded-full overflow-hidden border-2 !border-accent flex-shrink-0">
                    <img
                        src="<?= empty($user['image']) ? $default_img : dir::getAsset('Uploads/' . $user['image']); ?>"
                        alt="Profile Image"
                        class="w-full h-full object-cover object-center">
                </div>

                <!-- Info -->
                <div>
                    <h3 class="text-2xl font-semibold text-textPrimary"><?= htmlspecialchars($user['name'] ?? 'N/A'); ?></h3>
                    <p class="text-textPrimary">@<?= htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                    <p class="text-accent font-medium capitalize"><?= htmlspecialchars($user['role'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <!-- Right: More Details (60%) -->
            <div class="w-full md:w-3/5 grid grid-cols-2 gap-4">
                <!-- Branch -->
                <div>
                    <p class="text-md font-medium text-textPrimary">Branch</p>
                    <p class="text-textPrimary font-medium text-sm">
                        <?php
                        $branches = DB::fetch('branches', [], 'all');
                        $branch_name = 'N/A';
                        foreach ($branches as $branch) {
                            if ($branch['id'] == ($user['branch_id'] ?? '')) {
                                $branch_name = $branch['name'];
                                break;
                            }
                        }
                        echo htmlspecialchars($branch_name);
                        ?>
                    </p>
                </div>

                <!-- Permissions -->
                <div>
                    <p class="text-md font-medium text-textPrimary">Permissions</p>
                    <p class="text-textPrimary font-medium text-sm">
                        <?= $user['role'] === 'superadmin' ? 'All' : (!empty($user['allowed_routes']) ? htmlspecialchars(implode(', ', $user['allowed_routes'])) : 'None'); ?>
                    </p>
                </div>

                <!-- Color Theme -->
                <div>
                    <p class="text-md font-medium text-textPrimary">Color Scheme</p>
                    <div class="flex space-x-1 mt-1">
                        <?php
                        $colors = $user['preferences']['color_theme'] ?? [
                            'bg' => '#ffffff',
                            'accent' => '#4636ff',
                            'textPrimary' => '#0f172a',
                            'textSecondary' => '#989ea6'
                        ];
                        foreach ($colors as $key => $value): ?>
                            <div class="relative group w-6 h-6 cursor-pointer rounded-full border" style="background-color: <?= htmlspecialchars($value); ?>">
                                <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-2 py-1 rounded bg-gray-800 text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10 whitespace-nowrap">
                                    <?= htmlspecialchars($value); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                <!-- Entries per Page -->
                <div>
                    <p class="text-md font-medium text-textPrimary">Entries Per Page</p>
                    <p class="text-textPrimary font-medium text-sm"><?= $user['preferences']['entries_per_page'] ?? 'Not Set!'; ?></p>
                </div>
            </div>

        </div>
    </div>


    <!-- Personal Details Card -->
    <div class="bg-bg border-2 border-textSecondary rounded-lg p-4">
        <h3 class="text-xl font-semibold text-textPrimary mb-4">Personal Details</h3>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-md font-medium text-textPrimary">Father's Name</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['father_name'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Gender</p>
                <p class="text-textPrimary font-medium text-sm capitalize">
                    <?php
                    $genders = DB::fetch('static_types', ['type_for' => 'gender'], 'all');
                    $gender_name = 'N/A';
                    foreach ($genders as $gender) {
                        if ($gender['value1'] == ($user['details']['gender'] ?? '')) {
                            $gender_name = $gender['name'];
                            break;
                        }
                    }
                    echo htmlspecialchars($gender_name);
                    ?>
                </p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">ID Type</p>
                <p class="text-textPrimary font-medium text-sm">
                    <?php
                    $id_types = DB::fetch('static_types', ['type_for' => 'id_type'], 'all');
                    $id_type_name = 'N/A';
                    foreach ($id_types as $id_type) {
                        if ($id_type['value1'] == ($user['details']['id_type'] ?? '')) {
                            $id_type_name = $id_type['name'];
                            break;
                        }
                    }
                    echo htmlspecialchars($id_type_name);
                    ?>
                </p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">ID Number</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['id_number'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">ID Registration Date</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['id_reg_date'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">ID Expiry Date</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['id_exp_date'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Country</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['country'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">State</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['state'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">City</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['city'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Phone</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['phone'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">WhatsApp</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['whatsapp'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Postal Code</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['postal_code'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Email</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['email'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Account Number</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['account_no'] ?? 'N/A'); ?></p>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <p class="text-md font-medium text-textPrimary">Address</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($user['details']['address'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</section>