<?php
$user = DB::fetch('users', ['id' => $_SESSION['user']['id']], 'one');
$user['allowed_routes'] = jd($user['allowed_routes'] ?? '[]');
$user['details'] = jd($user['details'] ?? '[]');
$user['preferences'] = jd($user['preferences'] ?? '[]');
$routes = DB::fetch('navbar', [], 'all');
$default_img = 'https://placehold.co/100/FFFFFF/000?text=SELECT\nIMAGE';
?>
<section class="p-3 max-w-5xl mx-auto space-y-3">
    <h2 class="text-2xl font-semibold text-textPrimary mb-4">Edit Profile</h2>
    <form method="POST" action="<?= App::parentPath() . '/action' ?>" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= $_SESSION['user']['id'] ?? '' ?>">
        <div class="flex items-center space-x-4">
            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(event)">
            <label for="profile_image" class="cursor-pointer">
                <img id="imagePreview" src="<?= empty($user['image']) ? $default_img : dir::getAsset('uploads/' . $user['image']); ?>" alt="Select Image"
                    class="w-24 h-24 object-cover rounded-full border border-textPrimary hover:ring-2 hover:ring-accent transition duration-200 ease-in-out" />
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="name" placeholder="Name" value="<?= $user['name'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>

            <input type="text" name="username" placeholder="Username" value="<?= $user['username'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition" required>

            <input type="password" name="password" placeholder="New Password" value=""
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <select name="role" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <?php
                $roles = DB::fetch('static_types', ['type_for' => 'role'], 'all');
                if (($user['role'] ?? '') === 'superadmin') {
                    echo '<option value="superadmin" selected>Super Admin</option>';
                } else {
                    echo '<option value="" ' . (empty($user['role']) ? 'selected' : '') . '>Select</option>';
                    foreach ($roles as $role) {
                        if ($role['value1'] === 'superadmin') continue;
                        $slctd = $role['value1'] == ($user['role'] ?? '') ? 'selected' : '';
                        echo '<option value="' . $role['value1'] . '" ' . $slctd . '>' . $role['name'] . '</option>';
                    }
                }
                ?>
            </select>

            <select name="branch_id" required
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-1 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="sf" selected>Select</option>
                <?php $branches = DB::fetch('branches', [], 'all');
                foreach ($branches as $branch) {
                    $slctd = $branch['id'] == ($user['branch_id'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $branch['id'] . '"' . $slctd . '>' . $branch['name'] . '</option>';
                }
                ?>
            </select>
            <div
                x-data='{
    open: false, 
    selected: <?= htmlspecialchars(json_encode($user["allowed_routes"] ?? [])) ?>
}'
                class="relative w-full">
                <div @click="open = !open" class="border !border-textPrimary rounded-md px-3 py-2 !bg-white cursor-pointer">
                    <template x-if="selected.length">
                        <span x-text="selected.join(', ')" class="text-sm"></span>
                    </template>
                    <template x-if="!selected.length">
                        <span class="text-sm text-textPrimary">Select permissions...</span>
                    </template>
                </div>
                <div x-show="open" @click.outside="open = false" class="absolute z-50 mt-1 w-full bg-white border rounded shadow p-2 max-h-60 overflow-y-auto">
                    <?php
                    foreach ($routes as $index => $route): ?>
                        <label class="flex items-center gap-2 py-1 px-2 hover:bg-gray-100 rounded cursor-pointer">
                            <input
                                type="checkbox"
                                class="form-checkbox text-blue-500"
                                :value="'<?= $route['slug'] ?>'"
                                x-model="selected">
                            <span class="text-sm"><?= $route['label'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="allowed_routes" :value="selected.join(',')">
            </div>
        </div>
        <button type="submit" name="userSubmit"
            class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
            Save
        </button>
    </form>
</section>
<hr class="my-3 border-t border-gray-300">
<section id="personal-section" class="p-3 max-w-5xl mx-auto space-y-3">
    <h2 class="text-xl font-semibold text-textPrimary mb-4">Personal Details</h2>
    <form method="POST" action="<?= App::parentPath() . '/action' ?>" class="space-y-4">
        <input type="hidden" name="id" value="<?= $_SESSION['user']['id'] ?? '' ?>">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="father_name" placeholder="Father's Name" value="<?= $user['details']['father_name'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
            <select name="gender"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select</option>
                <?php $genders = DB::fetch('static_types', ['type_for' => 'gender'], 'all');
                foreach ($genders as $gender) {
                    $slctd = $gender['value1'] == ($user['details']['gender'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $gender['value1'] . '"' . $slctd . '>' . $gender['name'] . '</option>';
                }
                ?>
            </select>

            <select name="id_type"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-accent transition">
                <option value="" selected>Select</option>
                <?php $id_types = DB::fetch('static_types', ['type_for' => 'id_type'], 'all');
                foreach ($id_types as $id_type) {
                    $slctd = $id_type['value1'] == ($user['details']['id_type'] ?? '') ? 'selected' : '';
                    echo '<option value="' . $id_type['value1'] . '"' . $slctd . '>' . $id_type['name'] . '</option>';
                }
                ?>
            </select>
            <input type="text" name="id_number" placeholder="ID Number" value="<?= $user['details']['id_number'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="date" name="id_reg_date" value="<?= $user['details']['id_reg_date'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
            <input type="date" name="id_exp_date" value="<?= $user['details']['id_exp_date'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="text" name="country" placeholder="Country" value="<?= $user['details']['country'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="text" name="state" placeholder="State" value="<?= $user['details']['state'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="city" placeholder="City" value="<?= $user['details']['city'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
            <input type="number" name="phone" placeholder="Phone" value="<?= $user['details']['phone'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="number" name="whatsapp" placeholder="WhatsApp" value="<?= $user['details']['whatsapp'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">

            <input type="text" name="postal_code" placeholder="Email Address" value="<?= $user['details']['postal_code'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="email" name="email" placeholder="Postal Code" value="<?= $user['details']['email'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
            <input type="text" name="account_no" placeholder="Search Acc. No" value="<?= $user['details']['account_no'] ?? ''; ?>"
                class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition">
            <input type="hidden" name="account_id" value="<?= $user['details']['account_id'] ?? ''; ?>">
        </div>

        <textarea name="address" rows="2" placeholder="Address" value="<?= $user['details']['address'] ?? ''; ?>"
            class="w-full border border-textSecondary/30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent transition resize-none"><?= $user['details']['address'] ?? ''; ?></textarea>
        <button type="submit" name="personalSubmit"
            class="px-4 py-1.5 font-medium rounded-lg border !border-accent !bg-accent !text-bg text-sm hover:!bg-accent/90 transition">
            Save
        </button>
    </form>
</section>

<!-- JS for Image Preview -->
<script>
    function previewImage(event) {
        const file = event.target.files[0];
        const img = document.getElementById('imagePreview');
        if (file) {
            img.src = URL.createObjectURL(file);
        } else {
            img.src = "<?= $default_img; ?>";
        }
    }
</script>