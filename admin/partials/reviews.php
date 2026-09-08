<?php

$allProductReviews = gawdee_all_product_reviews();
$reviewEditId = (int) ($_GET['edit'] ?? 0);
$editReview = null;
foreach ($allProductReviews as $candidate) {
    if ((int) $candidate['id'] === $reviewEditId) {
        $editReview = $candidate;
        break;
    }
}
$review = $editReview ?? [
    'id' => 0, 'product_id' => $products[0]['id'] ?? '', 'rating' => 5, 'review' => '',
    'name' => '', 'email' => '', 'status' => 'approved',
];
?>
<div class="admin-section-title">
    <div><h2>Product review manager</h2><p>Add, edit, approve, hide and permanently remove reviews displayed on product pages.</p></div>
    <a class="admin-button admin-button--primary" href="?view=reviews&edit=-1"><i class="ph ph-plus"></i> Add product review</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editReview ? 'Edit product review' : 'New product review' ?></h2><p>Approved reviews are shown immediately on the related product page.</p></div><a href="?view=reviews" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_product_review">
            <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
            <label><span>Product</span><select name="product_id" required><?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['id']) ?>" <?= $review['product_id'] === $product['id'] ? 'selected' : '' ?>><?= htmlspecialchars($product['full_name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Customer name</span><input name="name" required value="<?= htmlspecialchars($review['name']) ?>"></label>
            <label><span>Customer email</span><input type="email" name="email" required value="<?= htmlspecialchars($review['email']) ?>"></label>
            <label><span>Rating</span><select name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>" <?= (int) $review['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> star<?= $rating === 1 ? '' : 's' ?></option><?php endfor; ?></select></label>
            <label><span>Publishing status</span><select name="status"><option value="approved" <?= $review['status'] === 'approved' ? 'selected' : '' ?>>Approved</option><option value="pending" <?= $review['status'] === 'pending' ? 'selected' : '' ?>>Hidden / pending</option></select></label>
            <label class="form-span-3"><span>Review</span><textarea name="review" required><?= htmlspecialchars($review['review']) ?></textarea></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save product review <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="admin-card">
<?php if ($allProductReviews): ?>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Product</th><th>Review</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($allProductReviews as $item): ?><tr>
        <td><strong><?= htmlspecialchars($item['name']) ?></strong><br><small><?= htmlspecialchars($item['email']) ?></small></td>
        <td><?= htmlspecialchars($item['product_name'] ?: $item['product_id']) ?></td>
        <td style="max-width:340px"><?= htmlspecialchars($item['review']) ?><br><small><?= htmlspecialchars($item['created_at']) ?></small></td>
        <td><span style="color:#e69613"><?= str_repeat('★', (int) $item['rating']) ?></span></td>
        <td><span class="status-pill <?= $item['status'] === 'approved' ? '' : 'status-pill--draft' ?>"><?= htmlspecialchars($item['status']) ?></span></td>
        <td><div class="admin-actions"><a class="admin-action-icon" href="?view=reviews&edit=<?= (int) $item['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_product_review"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon" title="Toggle approval"><i class="ph <?= $item['status'] === 'approved' ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this product review?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_product_review"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
<?php else: ?><div class="empty-state"><i class="ph ph-star"></i><h3>No product reviews yet</h3><p>Customer-submitted reviews and reviews created here will appear in this list.</p></div><?php endif; ?>
</section>
