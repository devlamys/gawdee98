<?php

$posts = gawdee_db()->query('SELECT * FROM blog_posts ORDER BY id DESC')->fetchAll();
$postEditId = (int) ($_GET['edit'] ?? 0);
$editPost = null;
foreach ($posts as $candidate) {
    if ((int) $candidate['id'] === $postEditId) {
        $editPost = $candidate;
        break;
    }
}
$post = $editPost ?? [
    'id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'status' => 'draft',
    'meta_description' => '', 'featured_image' => '', 'category' => 'Wellness', 'author' => 'Gawdee editorial', 'is_featured' => 0,
];
?>
<div class="admin-section-title">
    <div><h2>Stories & publishing</h2><p>Create image-led articles, save drafts, publish instantly or use the configured AI provider.</p></div>
    <div class="admin-actions"><a class="admin-button admin-button--secondary" href="?view=ai"><i class="ph ph-sparkle"></i> Generate with AI</a><a class="admin-button admin-button--primary" href="?view=blog&edit=-1"><i class="ph ph-plus"></i> New post</a></div>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editPost ? 'Edit article' : 'New article' ?></h2><p>Featured images appear on the homepage, journal and article header.</p></div><a href="?view=blog" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_blog">
            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
            <input type="hidden" name="existing_featured_image" value="<?= htmlspecialchars($post['featured_image']) ?>">
            <label class="form-span-2"><span>Title</span><input name="title" required value="<?= htmlspecialchars($post['title']) ?>"></label>
            <label><span>Slug</span><input name="slug" value="<?= htmlspecialchars($post['slug']) ?>" placeholder="Generated from title"></label>
            <label><span>Category</span><input name="category" value="<?= htmlspecialchars($post['category']) ?>"></label>
            <label><span>Author</span><input name="author" value="<?= htmlspecialchars($post['author']) ?>"></label>
            <label><span>Status</span><select name="status"><option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
            <label class="form-span-3"><span>Excerpt</span><textarea name="excerpt" style="min-height:85px"><?= htmlspecialchars($post['excerpt']) ?></textarea></label>
            <label class="form-span-3"><span>Article HTML</span><textarea name="content" style="min-height:360px" required><?= htmlspecialchars($post['content']) ?></textarea><small class="help-text">Allowed formatting includes headings, paragraphs, lists, emphasis, links and blockquotes.</small></label>
            <label class="form-span-2"><span>Meta description</span><textarea name="meta_description" style="min-height:82px"><?= htmlspecialchars($post['meta_description']) ?></textarea></label>
            <label><span>Featured image</span><input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($post['featured_image']) ?></small></label>
            <?php if ($post['featured_image']): ?><div class="blog-image-preview form-span-2"><img src="../<?= htmlspecialchars($post['featured_image']) ?>" alt=""></div><?php endif; ?>
            <label class="form-switch"><input type="checkbox" name="is_featured" <?= $post['is_featured'] ? 'checked' : '' ?>><span>Feature on homepage</span></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save article <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="admin-card">
    <?php if ($posts): ?>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Article</th><th>Category</th><th>Source</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($posts as $item): ?><tr><td><div class="admin-table__product"><?php if ($item['featured_image']): ?><img src="../<?= htmlspecialchars($item['featured_image']) ?>" alt=""><?php else: ?><span class="admin-table__placeholder"><i class="ph ph-article"></i></span><?php endif; ?><div><strong><?= htmlspecialchars($item['title']) ?></strong><span>/blog-post.php?slug=<?= htmlspecialchars($item['slug']) ?></span></div></div></td><td><?= htmlspecialchars($item['category']) ?><?= $item['is_featured'] ? '<br><small>Homepage featured</small>' : '' ?></td><td><?= htmlspecialchars($item['source'] . ($item['ai_provider'] ? ' · ' . $item['ai_provider'] : '')) ?></td><td><span class="status-pill status-pill--<?= htmlspecialchars($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span></td><td><?= htmlspecialchars($item['updated_at']) ?></td><td><div class="admin-actions"><a class="admin-action-icon" href="?view=blog&edit=<?= (int) $item['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_blog"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon"><i class="ph <?= $item['status'] === 'published' ? 'ph-eye-slash' : 'ph-paper-plane-tilt' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this blog post?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_blog"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><i class="ph ph-article"></i><h3>No stories yet</h3><p>Create a manual post or generate a draft in AI Studio.</p></div><?php endif; ?>
</section>
