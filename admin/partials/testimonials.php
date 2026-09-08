<?php

$allTestimonials = gawdee_testimonials(true);
$testimonialEditId = (int) ($_GET['edit'] ?? 0);
$editTestimonial = null;
foreach ($allTestimonials as $candidate) {
    if ((int) $candidate['id'] === $testimonialEditId) {
        $editTestimonial = $candidate;
        break;
    }
}
$testimonial = $editTestimonial ?? [
    'id' => 0, 'name' => '', 'initials' => '', 'avatar' => '', 'product_name' => '', 'product_slug' => '',
    'quote' => '', 'rating' => 5, 'theme' => 'ghee', 'sort_order' => count($allTestimonials) * 10 + 10, 'is_active' => 1,
];
?>
<div class="admin-section-title">
    <div><h2>Customer story manager</h2><p>Add, edit, order and publish the testimonial cards shown on the homepage.</p></div>
    <a class="admin-button admin-button--primary" href="?view=testimonials&edit=-1"><i class="ph ph-plus"></i> Add testimonial</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editTestimonial ? 'Edit customer story' : 'New customer story' ?></h2><p>Avatar images are optional; initials are used automatically.</p></div><a href="?view=testimonials" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_testimonial">
            <input type="hidden" name="id" value="<?= (int) $testimonial['id'] ?>">
            <input type="hidden" name="existing_avatar" value="<?= htmlspecialchars($testimonial['avatar']) ?>">
            <label><span>Customer name</span><input name="name" required value="<?= htmlspecialchars($testimonial['name']) ?>"></label>
            <label><span>Initials</span><input name="initials" maxlength="3" value="<?= htmlspecialchars($testimonial['initials']) ?>" placeholder="Auto"></label>
            <label><span>Rating</span><select name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>" <?= (int) $testimonial['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> star<?= $rating === 1 ? '' : 's' ?></option><?php endfor; ?></select></label>
            <label><span>Related product</span><select name="product_slug"><option value="">No product link</option><?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['slug']) ?>" <?= $testimonial['product_slug'] === $product['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($product['full_name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Product display name</span><input name="product_name" value="<?= htmlspecialchars($testimonial['product_name']) ?>"></label>
            <label><span>Colour theme</span><select name="theme"><?php foreach (['honey', 'ghee', 'mixme', 'moringa', 'forest'] as $theme): ?><option value="<?= $theme ?>" <?= $testimonial['theme'] === $theme ? 'selected' : '' ?>><?= ucfirst($theme) ?></option><?php endforeach; ?></select></label>
            <label class="form-span-3"><span>Testimonial</span><textarea name="quote" required><?= htmlspecialchars($testimonial['quote']) ?></textarea></label>
            <label><span>Customer avatar</span><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($testimonial['avatar']) ?></small></label>
            <label><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $testimonial['sort_order'] ?>"></label>
            <label class="form-switch"><input type="checkbox" name="is_active" <?= $testimonial['is_active'] ? 'checked' : '' ?>><span>Published on homepage</span></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save testimonial <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="testimonial-admin-grid">
    <?php foreach ($allTestimonials as $story): ?>
        <article class="testimonial-admin-card <?= $story['is_active'] ? '' : 'is-hidden' ?>">
            <div class="testimonial-admin-card__head">
                <?php if ($story['avatar']): ?><img src="../<?= htmlspecialchars($story['avatar']) ?>" alt=""><?php else: ?><span><?= htmlspecialchars($story['initials']) ?></span><?php endif; ?>
                <div><h3><?= htmlspecialchars($story['name']) ?></h3><small><?= str_repeat('★', $story['rating']) ?> · <?= htmlspecialchars($story['product_name'] ?: 'Customer story') ?></small></div>
            </div>
            <blockquote>“<?= htmlspecialchars($story['quote']) ?>”</blockquote>
            <footer><small>Order <?= $story['sort_order'] ?> · <?= $story['is_active'] ? 'Published' : 'Hidden' ?></small><div class="admin-actions"><a class="admin-action-icon" href="?view=testimonials&edit=<?= $story['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_testimonial"><input type="hidden" name="id" value="<?= $story['id'] ?>"><button class="admin-action-icon"><i class="ph <?= $story['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this testimonial?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_testimonial"><input type="hidden" name="id" value="<?= $story['id'] ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div></footer>
        </article>
    <?php endforeach; ?>
</div>
