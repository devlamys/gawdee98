<?php

$sections = gawdee_sections();
?>
<div class="admin-section-title">
    <div>
        <h2>Homepage content studio</h2>
        <p>Edit copy, calls to action, section artwork, mobile artwork and video links from one screen.</p>
    </div>
    <div class="admin-actions">
        <a class="admin-button admin-button--secondary" href="?view=section-items"><i class="ph ph-list-bullets"></i> Manage section items</a>
        <a class="admin-button admin-button--secondary" href="?view=media"><i class="ph ph-video-camera"></i> Manage cards & videos</a>
        <a class="admin-button admin-button--secondary" href="../index.php" target="_blank"><i class="ph ph-arrow-square-out"></i> Preview homepage</a>
    </div>
</div>

<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2>Global website typography</h2><p>Apply a clean, normal web font and readable base size across the storefront.</p></div><span class="status-pill">Site-wide</span></div>
    <div class="admin-card__body">
        <form method="post" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_typography">
            <label><span>Body font</span><select name="site_body_font"><option value="system" <?= gawdee_setting('site_body_font', 'system') === 'system' ? 'selected' : '' ?>>System UI (recommended)</option><option value="arial" <?= gawdee_setting('site_body_font') === 'arial' ? 'selected' : '' ?>>Arial</option><option value="dm-sans" <?= gawdee_setting('site_body_font') === 'dm-sans' ? 'selected' : '' ?>>DM Sans</option></select></label>
            <label><span>Heading font</span><select name="site_heading_font"><option value="system" <?= gawdee_setting('site_heading_font', 'system') === 'system' ? 'selected' : '' ?>>System UI (recommended)</option><option value="arial" <?= gawdee_setting('site_heading_font') === 'arial' ? 'selected' : '' ?>>Arial</option><option value="dm-sans" <?= gawdee_setting('site_heading_font') === 'dm-sans' ? 'selected' : '' ?>>DM Sans</option></select></label>
            <label><span>Base font size</span><select name="site_base_font_size"><?php foreach ([14, 15, 16, 17, 18, 19, 20] as $size): ?><option value="<?= $size ?>" <?= (int) gawdee_setting('site_base_font_size', '16') === $size ? 'selected' : '' ?>><?= $size ?> px</option><?php endforeach; ?></select></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save website typography <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_cms">
    <div class="cms-editor-list">
        <?php foreach ($sections as $key => $section): ?>
            <details class="cms-editor" <?= in_array($key, ['offer', 'reviews', 'stories', 'reels'], true) ? 'open' : '' ?>>
                <summary>
                    <span class="cms-card__key"><i class="ph ph-layout"></i></span>
                    <span><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></strong><small><?= htmlspecialchars($section['title'] ?: 'Untitled section') ?></small></span>
                    <span class="status-pill <?= $section['is_active'] ? '' : 'status-pill--draft' ?>"><?= $section['is_active'] ? 'Visible' : 'Hidden' ?></span>
                    <i class="ph ph-caret-down"></i>
                </summary>
                <div class="cms-editor__body form-grid form-grid--3">
                    <label><span>Eyebrow</span><input name="eyebrow[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['eyebrow']) ?>"></label>
                    <label><span>Section title</span><input name="title[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['title']) ?>"></label>
                    <label><span>Sort order</span><input type="number" name="sort_order[<?= htmlspecialchars($key) ?>]" value="<?= (int) $section['sort_order'] ?>"></label>
                    <label class="form-span-3"><span>Subtitle</span><input name="subtitle[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['subtitle']) ?>"></label>
                    <label class="form-span-3"><span>Supporting content</span><textarea name="body[<?= htmlspecialchars($key) ?>]"><?= htmlspecialchars($section['body']) ?></textarea></label>
                    <label><span>Button label</span><input name="button_label[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['button_label']) ?>" placeholder="Explore collection"></label>
                    <label class="form-span-2"><span>Button destination</span><input name="button_url[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['button_url']) ?>" placeholder="#shop or products.php"></label>
                    <label class="form-span-3"><span>Video URL</span><input name="video_url[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['video_url']) ?>" placeholder="Optional YouTube, Vimeo or uploaded video URL"></label>

                    <div class="cms-media-field">
                        <label><span>Desktop / primary image</span><input type="file" name="section_image_<?= htmlspecialchars($key) ?>" accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="image[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['image']) ?>">
                        <?php if ($section['image']): ?><img src="../<?= htmlspecialchars($section['image']) ?>" alt="" loading="lazy"><small><?= htmlspecialchars($section['image']) ?></small><?php endif; ?>
                    </div>
                    <div class="cms-media-field">
                        <label><span>Mobile image</span><input type="file" name="section_mobile_image_<?= htmlspecialchars($key) ?>" accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="mobile_image[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['mobile_image']) ?>">
                        <?php if ($section['mobile_image']): ?><img src="../<?= htmlspecialchars($section['mobile_image']) ?>" alt="" loading="lazy"><small><?= htmlspecialchars($section['mobile_image']) ?></small><?php endif; ?>
                    </div>
                    <label class="form-switch"><input type="checkbox" name="active[<?= htmlspecialchars($key) ?>]" <?= $section['is_active'] ? 'checked' : '' ?>><span>Show this section</span></label>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
    <div class="sticky-save-bar"><span><i class="ph ph-info"></i> Changes are published immediately.</span><button class="admin-button admin-button--primary">Save homepage content <i class="ph ph-check"></i></button></div>
</form>
