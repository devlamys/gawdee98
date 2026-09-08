<?php

$allMedia = gawdee_homepage_media(null, true);
$mediaEditId = (int) ($_GET['edit'] ?? 0);
$editMedia = null;
foreach ($allMedia as $candidate) {
    if ((int) $candidate['id'] === $mediaEditId) {
        $editMedia = $candidate;
        break;
    }
}
$media = $editMedia ?? [
    'id' => 0, 'section_key' => 'reels', 'media_type' => 'image', 'title' => '', 'subtitle' => '',
    'file_path' => '', 'poster_path' => '', 'external_url' => '', 'link_url' => '', 'alt_text' => '',
    'product_slug' => '', 'sort_order' => count($allMedia) * 10 + 10, 'is_active' => 1,
];
$sectionOptions = array_keys(gawdee_sections());
?>
<div class="admin-section-title">
    <div><h2>Homepage media library</h2><p>Manage reusable image cards, uploaded videos, posters and external video links.</p></div>
    <a class="admin-button admin-button--primary" href="?view=media&edit=-1"><i class="ph ph-plus"></i> Add media</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editMedia ? 'Edit media card' : 'New media card' ?></h2><p>Use the Reels section for the homepage “From nature to your plate” cards.</p></div><a href="?view=media" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_homepage_media">
            <input type="hidden" name="id" value="<?= (int) $media['id'] ?>">
            <input type="hidden" name="existing_file_path" value="<?= htmlspecialchars($media['file_path']) ?>">
            <input type="hidden" name="existing_poster_path" value="<?= htmlspecialchars($media['poster_path']) ?>">
            <label><span>Homepage section</span><select name="section_key"><?php foreach ($sectionOptions as $sectionKey): ?><option value="<?= htmlspecialchars($sectionKey) ?>" <?= $media['section_key'] === $sectionKey ? 'selected' : '' ?>><?= htmlspecialchars(ucwords($sectionKey)) ?></option><?php endforeach; ?></select></label>
            <label><span>Media type</span><select name="media_type"><option value="image" <?= $media['media_type'] === 'image' ? 'selected' : '' ?>>Image</option><option value="video" <?= $media['media_type'] === 'video' ? 'selected' : '' ?>>Uploaded video</option><option value="external_video" <?= $media['media_type'] === 'external_video' ? 'selected' : '' ?>>External video</option></select></label>
            <label><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $media['sort_order'] ?>"></label>
            <label><span>Title</span><input name="title" value="<?= htmlspecialchars($media['title']) ?>"></label>
            <label class="form-span-2"><span>Subtitle</span><input name="subtitle" value="<?= htmlspecialchars($media['subtitle']) ?>"></label>
            <label><span>Upload image or video</span><input type="file" name="media_file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/ogg"><small class="help-text"><?= htmlspecialchars($media['file_path']) ?></small></label>
            <label><span>Video poster</span><input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($media['poster_path']) ?></small></label>
            <label><span>External video URL</span><input name="external_url" value="<?= htmlspecialchars($media['external_url']) ?>" placeholder="https://youtube.com/..."></label>
            <label><span>Related product</span><select name="product_slug"><option value="">No related product</option><?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['slug']) ?>" <?= $media['product_slug'] === $product['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($product['full_name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Click destination</span><input name="link_url" value="<?= htmlspecialchars($media['link_url']) ?>"></label>
            <label><span>Accessible alt text</span><input name="alt_text" value="<?= htmlspecialchars($media['alt_text']) ?>"></label>
            <label class="form-switch"><input type="checkbox" name="is_active" <?= $media['is_active'] ? 'checked' : '' ?>><span>Published on homepage</span></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save media card <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="media-admin-grid">
    <?php foreach ($allMedia as $item): ?>
        <article class="media-admin-card <?= $item['is_active'] ? '' : 'is-hidden' ?>">
            <div class="media-admin-card__visual">
                <?php if ($item['media_type'] === 'image' && $item['file_path']): ?><img src="../<?= htmlspecialchars($item['file_path']) ?>" alt="">
                <?php elseif ($item['poster_path']): ?><img src="../<?= htmlspecialchars($item['poster_path']) ?>" alt=""><i class="ph ph-play-circle"></i>
                <?php else: ?><i class="ph <?= $item['media_type'] === 'image' ? 'ph-image' : 'ph-video-camera' ?>"></i><?php endif; ?>
                <span><?= htmlspecialchars(str_replace('_', ' ', $item['media_type'])) ?></span>
            </div>
            <div class="media-admin-card__body"><small><?= htmlspecialchars(ucfirst($item['section_key'])) ?> · Order <?= $item['sort_order'] ?></small><h3><?= htmlspecialchars($item['title'] ?: 'Untitled media') ?></h3><p><?= htmlspecialchars($item['subtitle']) ?></p><div class="admin-actions"><a class="admin-action-icon" href="?view=media&edit=<?= $item['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_homepage_media"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button class="admin-action-icon"><i class="ph <?= $item['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this media card?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_homepage_media"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div></div>
        </article>
    <?php endforeach; ?>
</div>
