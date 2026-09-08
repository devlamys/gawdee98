<?php $design = gawdee_site(); ?>
<div class="admin-section-title"><div><h2>Brand, navigation & help pages</h2><p>These settings update the header, footer, colours, login and help pages throughout the store.</p></div><a class="admin-button admin-button--secondary" href="../index.php" target="_blank" rel="noopener">View store <i class="ph ph-arrow-square-out"></i></a></div>
<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_site_design">
    <?php foreach (gawdee_site_fields() as $group => $fields): ?>
    <section class="admin-card"><div class="admin-card__header"><h2><?= htmlspecialchars($group) ?></h2></div><div class="admin-card__body form-grid">
        <?php foreach ($fields as $key => [$label,$type]): ?><label class="<?= $type === 'textarea' ? 'form-span-2' : '' ?> <?= $type === 'toggle' ? 'form-switch' : '' ?>">
        <?php if ($type === 'toggle'): ?><input type="checkbox" name="<?= $key ?>" value="1" <?= $design[$key] === '1' ? 'checked' : '' ?>><span><?= htmlspecialchars($label) ?></span>
        <?php elseif ($type === 'textarea'): ?><span><?= htmlspecialchars($label) ?></span><textarea name="<?= $key ?>" rows="5"><?= htmlspecialchars($design[$key]) ?></textarea>
        <?php elseif ($type === 'density'): ?><span><?= htmlspecialchars($label) ?></span><select name="<?= $key ?>"><option value="compact" <?= $design[$key] === 'compact' ? 'selected' : '' ?>>Compact</option><option value="comfortable" <?= $design[$key] === 'comfortable' ? 'selected' : '' ?>>Comfortable</option></select>
        <?php else: ?><span><?= htmlspecialchars($label) ?></span><input type="<?= $type === 'color' ? 'color' : 'text' ?>" name="<?= $key ?>" value="<?= htmlspecialchars($design[$key]) ?>">
        <?php endif; ?></label><?php endforeach; ?>
        <?php if ($group === 'Brand & appearance'): ?><label class="form-span-2"><span>Upload a new logo</span><input type="file" name="brand_logo_upload" accept="image/png,image/jpeg,image/webp"><small class="help-text">An uploaded file replaces the logo path above.</small></label><?php endif; ?>
    </div></section>
    <?php endforeach; ?>
    <div class="sticky-save-bar"><span>Changes apply across the storefront.</span><button class="admin-button admin-button--primary">Save brand & pages <i class="ph ph-check"></i></button></div>
</form>
