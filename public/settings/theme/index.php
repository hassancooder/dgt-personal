<?php
Doc::setLink('https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css', true);
Doc::setScript('https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js', true);
$currentColorScheme = App::currentUser()['preferences']['color_theme'];
?>

<form action="<?= App::currentPath() . '/action'; ?>" method="POST"
    class="max-w-2xl mx-auto mt-2 bg-bg text-textSecondary border border-textSecondary/20 rounded-2xl p-4 space-y-2">
    <h2 class="text-2xl font-bold border-b pb-2 text-textPrimary">Customize App Theme</h2>
    <?php
    $colors = [
        ['label' => 'Background Color', 'name' => 'bgColor', 'value' => $currentColorScheme['bg']],
        ['label' => 'Accent Color', 'name' => 'accentColor', 'value' => $currentColorScheme['accent']],
        ['label' => 'Text Primary', 'name' => 'textPrimary', 'value' => $currentColorScheme['textPrimary']],
        ['label' => 'Text Secondary', 'name' => 'textSecondary', 'value' => $currentColorScheme['textSecondary']],
    ];
    foreach ($colors as $color): ?>
        <div class="flex items-center justify-between gap-4 border border-textSecondary/20 p-2 rounded-lg">
            <label class="w-48 font-semibold text-textSecondary"><?= $color['label'] ?></label>
            <input
                type="text"
                name="<?= $color['name'] ?>"
                value="<?= $color['value'] ?>"
                class="coloris w-32 px-2 py-1 border rounded text-sm font-mono bg-white text-gray-700"
                data-coloris />
        </div>
    <?php endforeach; ?>

    <button type="submit" name="themeSubmit"
        class="mx-auto mt-4 py-1 px-3 !bg-accent !text-white text-md font-semibold rounded-lg hover:!bg-accent/90 transition">
        Save Theme
    </button>
</form>
<script>
    Coloris({
        theme: 'large',
        themeMode: 'auto',
        alpha: false,
        format: 'hex',
        swatches: [
            '#F87171', '#FACC15', '#34D399', '#60A5FA', '#A78BFA',
            '#F472B6', '#000000', '#FFFFFF', '#16414F'
        ]
    });
</script>