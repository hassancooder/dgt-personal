<?php
if (isset($_GET['id'])) {
    $branch = DB::fetch('branches', ['id' => $_GET['id']], 'one');
    $branch['details'] = jd($branch['details'] ?? '[]');
} else {
    $branch = ['details' => []];
}
?>
<section class="max-w-5xl mx-auto p-2">
    <div class="flex gap-2 items-center justify-between">
        <h2 class="text-3xl font-bold text-textPrimary mb-6">View Branch</h2>
        <div>
            <a href="<?= ''; ?>" class="!bg-textPrimary mx-1 !text-bg rounded-lg hover:!bg-textPrimary/90 transition text-sm px-2 py-2 font-medium">
                <i class="ri-upload-2-line text-xl !text-bg"></i>
            </a>
        </div>
    </div>
    <!-- Personal Details Card -->
    <div class="bg-bg border-2 border-textSecondary rounded-lg p-4">
        <h3 class="text-xl font-semibold text-textPrimary mb-4">Personal Details</h3>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-md font-medium text-textPrimary">Admin's Name</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['admin_name'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Father's Name</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['father_name'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Country</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['country'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">State</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['state'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">City</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['city'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Phone</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['phone'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">WhatsApp</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['whatsapp'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Postal Code</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['postal_code'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-md font-medium text-textPrimary">Email</p>
                <p class="text-textPrimary font-medium text-sm"><?= htmlspecialchars($branch['details']['email'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</section>