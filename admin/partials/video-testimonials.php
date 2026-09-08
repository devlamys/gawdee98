<?php

$allVideoTestimonials = gawdee_video_testimonials(true);
$videoTestimonialEditId = (int) ($_GET['edit'] ?? 0);
$editVideoTestimonial = null;
foreach ($allVideoTestimonials as $candidate) {
    if ((int) $candidate['id'] === $videoTestimonialEditId) {
        $editVideoTestimonial = $candidate;
        break;
    }
}
$videoTestimonial = $editVideoTestimonial ?? [
    'id' => 0, 'name' => '', 'role_location' => '', 'quote' => '', 'rating' => 5,
    'video_type' => 'upload', 'video_path' => '', 'poster_path' => '', 'external_url' => '',
    'sort_order' => count($allVideoTestimonials) * 10 + 10, 'is_active' => 1,
];
?>
<div class="admin-section-title">
    <div><h2>Video testimonial manager</h2><p>Add, edit, publish, hide and remove customer videos shown on the homepage.</p></div>
    <a class="admin-button admin-button--primary" href="?view=video-testimonials&edit=-1"><i class="ph ph-plus"></i> Add video testimonial</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editVideoTestimonial ? 'Edit video testimonial' : 'New video testimonial' ?></h2><p>Upload a video or use an external YouTube/Vimeo URL. A poster image is recommended.</p></div><a href="?view=video-testimonials" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_video_testimonial">
            <input type="hidden" name="id" value="<?= (int) $videoTestimonial['id'] ?>">
            <input type="hidden" name="existing_video_path" value="<?= htmlspecialchars($videoTestimonial['video_path']) ?>">
            <input type="hidden" name="existing_poster_path" value="<?= htmlspecialchars($videoTestimonial['poster_path']) ?>">
            <label><span>Customer name</span><input name="name" required value="<?= htmlspecialchars($videoTestimonial['name']) ?>"></label>
            <label><span>Role or location</span><input name="role_location" value="<?= htmlspecialchars($videoTestimonial['role_location']) ?>" placeholder="Dubai, UAE"></label>
            <label><span>Rating</span><select name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>" <?= (int) $videoTestimonial['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> star<?= $rating === 1 ? '' : 's' ?></option><?php endfor; ?></select></label>
            <label class="form-span-3"><span>Testimonial quote</span><textarea name="quote" required><?= htmlspecialchars($videoTestimonial['quote']) ?></textarea></label>
            <label><span>Video source</span><select name="video_type"><option value="upload" <?= $videoTestimonial['video_type'] === 'upload' ? 'selected' : '' ?>>Uploaded video</option><option value="external_video" <?= $videoTestimonial['video_type'] === 'external_video' ? 'selected' : '' ?>>External video URL</option></select></label>
            <label><span>Upload video</span><input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg"><small class="help-text">MP4, WebM or Ogg up to 60 MB. <?= htmlspecialchars($videoTestimonial['video_path']) ?></small></label>
            <label><span>Poster image</span><input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($videoTestimonial['poster_path']) ?></small></label>
            <label class="form-span-2"><span>External video URL</span><input type="url" name="external_url" value="<?= htmlspecialchars($videoTestimonial['external_url']) ?>" placeholder="https://www.youtube.com/watch?v=..."></label>
            <label><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $videoTestimonial['sort_order'] ?>"></label>
            <label class="form-switch"><input type="checkbox" name="is_active" <?= $videoTestimonial['is_active'] ? 'checked' : '' ?>><span>Published on homepage</span></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save video testimonial <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<?php if ($allVideoTestimonials): ?>
<div class="media-admin-grid">
    <?php foreach ($allVideoTestimonials as $item): ?>
    <article class="media-admin-card <?= $item['is_active'] ? '' : 'is-hidden' ?>">
        <div class="media-admin-card__visual">
            <?php if ($item['poster_path']): ?><img src="../<?= htmlspecialchars($item['poster_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"><?php endif; ?>
            <i class="ph ph-play-circle"></i><span><?= $item['video_type'] === 'upload' ? 'uploaded video' : 'external video' ?></span>
        </div>
        <div class="media-admin-card__body">
            <small><?= str_repeat('★', (int) $item['rating']) ?> · Order <?= (int) $item['sort_order'] ?> · <?= $item['is_active'] ? 'Published' : 'Hidden' ?></small>
            <h3><?= htmlspecialchars($item['name']) ?></h3><p><?= htmlspecialchars($item['quote']) ?></p>
            <div class="admin-actions"><a class="admin-action-icon" href="?view=video-testimonials&edit=<?= (int) $item['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_video_testimonial"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon"><i class="ph <?= $item['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this video testimonial?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_video_testimonial"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state admin-card"><i class="ph ph-video-camera"></i><h3>No video testimonials yet</h3><p>Add the first customer video and it will appear in the homepage video-stories section.</p></div>
<?php endif; ?>
