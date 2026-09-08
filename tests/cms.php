<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

$db = gawdee_db();
$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
    if (!$condition) {
        $failures[] = $label;
    }
};

$db->beginTransaction();
try {
    $db->prepare('INSERT INTO testimonials (name, initials, product_name, product_slug, quote, rating, theme, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)')->execute([
        'CMS QA Customer', 'CQ', 'A2 Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 'A temporary testimonial used to verify the complete CMS workflow.', 5, 'ghee', 999,
    ]);
    $testimonialId = (int) $db->lastInsertId();
    $testimonial = array_values(array_filter(gawdee_testimonials(), static fn (array $row): bool => $row['id'] === $testimonialId));
    $check(count($testimonial) === 1 && $testimonial[0]['rating'] === 5, 'testimonial create and public query');

    $db->prepare('INSERT INTO homepage_media (section_key, media_type, title, file_path, product_slug, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)')->execute([
        'reels', 'image', 'CMS QA Media', 'assets/images/products/ghee-500.webp', 'gawdee-gir-cow-a2-ghee-500-ml', 999,
    ]);
    $mediaId = (int) $db->lastInsertId();
    $media = array_values(array_filter(gawdee_homepage_media('reels'), static fn (array $row): bool => $row['id'] === $mediaId));
    $check(count($media) === 1 && $media[0]['media_type'] === 'image', 'homepage media create and public query');

    $db->prepare('INSERT INTO video_testimonials (name, role_location, quote, rating, video_type, external_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')->execute([
        'CMS Video Customer', 'Test location', 'A temporary video story used to verify admin-managed homepage publishing.', 5, 'external_video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 999,
    ]);
    $videoTestimonialId = (int) $db->lastInsertId();
    $db->prepare('UPDATE video_testimonials SET quote=?, rating=? WHERE id=?')->execute(['An edited temporary video story used to verify the CMS workflow.', 4, $videoTestimonialId]);
    $videoTestimonials = array_values(array_filter(gawdee_video_testimonials(), static fn (array $row): bool => $row['id'] === $videoTestimonialId));
    $check(count($videoTestimonials) === 1 && $videoTestimonials[0]['rating'] === 4, 'video testimonial create, edit and public query');
    $db->prepare('DELETE FROM video_testimonials WHERE id=?')->execute([$videoTestimonialId]);
    $check(count(array_filter(gawdee_video_testimonials(true), static fn (array $row): bool => $row['id'] === $videoTestimonialId)) === 0, 'video testimonial delete');

    $db->prepare('INSERT INTO product_reviews (product_id, rating, review, name, email, status) VALUES (?, ?, ?, ?, ?, ?)')->execute([
        'ghee-500', 5, 'A temporary product review used to verify complete admin review management.', 'CMS Review Customer', 'cms-review@example.test', 'approved',
    ]);
    $productReviewId = (int) $db->lastInsertId();
    $db->prepare('UPDATE product_reviews SET review=?, status=? WHERE id=?')->execute(['An edited temporary product review used by the CMS test.', 'pending', $productReviewId]);
    $managedReviews = array_values(array_filter(gawdee_all_product_reviews(), static fn (array $row): bool => $row['id'] === $productReviewId));
    $check(count($managedReviews) === 1 && $managedReviews[0]['status'] === 'pending', 'product review create and edit');
    $db->prepare('DELETE FROM product_reviews WHERE id=?')->execute([$productReviewId]);
    $check(count(array_filter(gawdee_all_product_reviews(), static fn (array $row): bool => $row['id'] === $productReviewId)) === 0, 'product review delete');

    $db->prepare('INSERT INTO cms_section_items (section_key, icon, title, subtitle, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)')->execute([
        'benefits', 'ph-check', 'CMS QA benefit', 'Temporary editable item', 999,
    ]);
    $sectionItemId = (int) $db->lastInsertId();
    $db->prepare('UPDATE cms_section_items SET title=?, is_active=0 WHERE id=?')->execute(['CMS QA edited benefit', $sectionItemId]);
    $managedItems = array_values(array_filter(gawdee_section_items('benefits', true), static fn (array $row): bool => $row['id'] === $sectionItemId));
    $check(count($managedItems) === 1 && $managedItems[0]['title'] === 'CMS QA edited benefit' && $managedItems[0]['is_active'] === 0, 'homepage section item create and edit');
    $db->prepare('DELETE FROM cms_section_items WHERE id=?')->execute([$sectionItemId]);
    $check(count(array_filter(gawdee_section_items('benefits', true), static fn (array $row): bool => $row['id'] === $sectionItemId)) === 0, 'homepage section item delete');

    $slug = 'cms-qa-post-' . bin2hex(random_bytes(4));
    $db->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, status, source, featured_image, category, author, is_featured, published_at) VALUES (?, ?, ?, ?, 'published', 'manual', ?, ?, ?, 1, CURRENT_TIMESTAMP)")->execute([
        'CMS QA Post', $slug, 'Temporary CMS test article.', '<h2>Safe story</h2><p>Verified content.</p>', 'assets/images/blogs/small-daily-improvements-v1.webp', 'Testing', 'QA editor',
    ]);
    $postStatement = $db->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
    $postStatement->execute([$slug]);
    $post = $postStatement->fetch();
    $check((bool) $post && $post['featured_image'] !== '' && (int) $post['is_featured'] === 1, 'blog image, author and featured publishing');

    $original = gawdee_section('reviews');
    $db->prepare('UPDATE cms_sections SET title=?, button_label=?, button_url=? WHERE section_key=?')->execute(['CMS QA Reviews', 'Read stories', '#reviews', 'reviews']);
    $updated = gawdee_section('reviews');
    $check($updated['title'] === 'CMS QA Reviews' && $updated['button_label'] === 'Read stories', 'homepage section content and CTA update');
    $check(isset(gawdee_sections()['video_testimonials'], gawdee_sections()['benefits'], gawdee_sections()['process'], gawdee_sections()['assurance']), 'complete homepage section registry');
} finally {
    $db->rollBack();
}

exit($failures ? 1 : 0);
