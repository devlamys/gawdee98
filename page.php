<?php
declare(strict_types=1);
require __DIR__ . '/includes/storefront.php';
$pages = ['shipping'=>'Shipping & Delivery','returns'=>'Returns & Refunds','contact'=>'Contact Us'];
$slug = (string)($_GET['slug'] ?? 'contact');
$found = isset($pages[$slug]);
if (!$found) http_response_code(404);
$site = gawdee_site();
$title = $pages[$slug] ?? 'Page not found';
$pageTitle = $title . ' | ' . $site['brand_name'];
$bodyClass = 'help-page';
require __DIR__ . '/includes/header.php';
?>
<section class="content-page"><div class="sf-container"><article class="content-page__article">
<a class="sf-text-link" href="index.php"><i class="ph ph-arrow-left"></i> Back to home</a>
<h1><?= htmlspecialchars($title) ?></h1>
<?php if ($found): ?>
<div class="content-page__copy"><?= nl2br(htmlspecialchars($site['page_'.$slug])) ?></div>
<div class="content-page__contact"><h2>How can we help?</h2>
<?php $email = gawdee_setting('store_email'); $phone = preg_replace('/[^+0-9]/','',gawdee_setting('whatsapp_number')); ?>
<?php if($email): ?><a class="sf-text-link" href="mailto:<?= htmlspecialchars($email) ?>"><i class="ph ph-envelope"></i> <?= htmlspecialchars($email) ?></a><?php endif; ?>
<?php if($phone): ?><a class="sf-text-link" href="https://wa.me/<?= htmlspecialchars(ltrim($phone,'+')) ?>" target="_blank" rel="noopener"><i class="ph ph-whatsapp-logo"></i> WhatsApp support</a><?php endif; ?>
<a class="sf-text-link" href="account.php"><i class="ph ph-package"></i> View your orders</a></div>
<?php else: ?><p>This page is unavailable. Explore our collection or contact our team for help.</p><a class="sf-button" href="products.php">Browse products</a><?php endif; ?>
</article></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
