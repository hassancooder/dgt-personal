<div class="mt-6 max-w-2xl bg-bg border border-textSecondary/20 rounded-xl p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-lg font-semibold text-textPrimary">App Theme</h4>
            <a href="<?= App::currentPath() . '/theme'; ?>" class="text-sm !text-accent hover:underline">Manage App Theme</a>
        </div>
    </div>

    <!-- Entries Per Page Form -->
    <form action="<?= App::currentPath() . '/action'; ?>" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
        <label for="entries_per_page" class="text-sm text-textPrimary font-medium">
            Entries Per Page:
        </label>

        <select name="entries_per_page" id="entries_per_page"
            class="w-full sm:w-auto px-3 py-2 border border-textSecondary/30 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-accent">
            <?php $EPP = DB::fetch('static_types', ['type_for' => 'entries_per_page'], 'all');
            $user_p = App::currentUser()['preferences'];
            $user_epp = !empty($user_p['entries_per_page']) ? $user_p['entries_per_page'] : '10';
            foreach ($EPP as $s_epp) {
                $slctd = $user_epp === $s_epp['value1'] ? 'selected' : '';
                echo '<option value="' . $s_epp['value1'] . '" ' . $slctd . '>' . $s_epp['name'] . '</option>';
            }
            ?>
        </select>

        <button type="submit" name="EPPSubmit"
            class="!bg-accent hover:!bg-accent/90 !text-bg font-medium px-4 py-2 rounded-md text-sm transition">
            Apply
        </button>
    </form>
</div>