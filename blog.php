<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';
$posts = gawdee_db()->query("SELECT * FROM blog_posts WHERE status='published' ORDER BY is_featured DESC, COALESCE(published_at, created_at) DESC")->fetchAll();
$pageTitle = 'Gawdee Journal — Food, tradition and wellness';
$pageDescription = 'Thoughtful articles about traditional food, ingredient knowledge and everyday family wellness.';
$bodyClass = 'blog-index-page';
$journalSection = gawdee_section('stories');
require __DIR__ . '/includes/header.php';
?>
<section class="journal-hero">
    <span class="content-eyebrow"><?= htmlspecialchars($journalSection['eyebrow']) ?></span>
    <h1><?= htmlspecialchars($journalSection['title'] ?: 'Journal') ?></h1>
    <p><?= htmlspecialchars($journalSection['subtitle']) ?></p>
</section>
<section class="journal-grid">
    <?php if ($posts): foreach ($posts as $post): ?>
        <article class="journal-card">
            <div class="journal-card__visual">
                <?php if ($post['featured_image']): ?><img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy"><?php else: ?><i class="ph ph-leaf"></i><?php endif; ?>
                <span><?= htmlspecialchars(($post['category'] ?: 'Wellness') . ' · ' . ($post['source'] === 'ai' ? 'AI-assisted' : 'Gawdee editorial')) ?></span>
            </div>
            <div class="journal-card__body">
                <small><?= htmlspecialchars(date('d M Y', strtotime($post['published_at'] ?: $post['created_at']))) ?> · <?= htmlspecialchars($post['author'] ?: 'Gawdee editorial') ?></small>
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <p><?= htmlspecialchars($post['excerpt']) ?></p>
                <a href="blog-post.php?slug=<?= rawurlencode($post['slug']) ?>">Read story <i class="ph ph-arrow-up-right"></i></a>
            </div>
        </article>
    <?php endforeach; else: ?>
        <div class="journal-empty"><i class="ph ph-article"></i><h2>Fresh stories are on the way.</h2><p>The Gawdee journal is being prepared with care.</p></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
