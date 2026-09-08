<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';
$statement = gawdee_db()->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
$statement->execute([trim((string) ($_GET['slug'] ?? ''))]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Story not found — Gawdee';
} else {
    $pageTitle = $post['title'] . ' — Gawdee Journal';
    $pageDescription = $post['meta_description'] ?: $post['excerpt'];
}
$bodyClass = 'blog-post-page';
require __DIR__ . '/includes/header.php';
?>
<?php if ($post): ?>
<article class="journal-article">
    <header>
        <a href="blog.php"><i class="ph ph-arrow-left"></i> All stories</a>
        <span class="content-eyebrow"><?= htmlspecialchars($post['category'] ?: 'Gawdee journal') ?></span>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?><p><?= htmlspecialchars($post['excerpt']) ?></p><?php endif; ?>
        <small><?= htmlspecialchars(date('d F Y', strtotime($post['published_at'] ?: $post['created_at']))) ?> · <?= htmlspecialchars($post['author'] ?: 'Gawdee editorial') ?></small>
    </header>
    <?php if ($post['featured_image']): ?><figure class="journal-article__cover"><img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>"></figure><?php endif; ?>
    <div class="journal-article__body"><?= $post['content'] ?></div>
    <footer><strong>Thoughtful food choices start with good information.</strong><a href="index.php#shop">Explore Gawdee products <i class="ph ph-arrow-right"></i></a></footer>
</article>
<?php else: ?>
<section class="journal-hero"><h1>Story not found.</h1><p>The article may have moved or is still being prepared.</p><a class="button button--primary" href="blog.php">Back to journal</a></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
