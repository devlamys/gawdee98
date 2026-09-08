<?php
declare(strict_types=1);
require __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/wishlist.php';
$pageTitle = 'Your wishlist | ' . gawdee_site()['brand_name'];
$bodyClass = 'wishlist-page';
$savedItems = gawdee_wishlist_items();
$catalogue = array_column($products,null,'id');
require __DIR__ . '/includes/header.php';
?>
<section class="sf-section"><div class="sf-container">
<header class="sf-section-heading"><div><span class="sf-eyebrow">Saved for later</span><h1>Your wishlist</h1><p><?= gawdee_customer() ? 'Your favourites, ready whenever you are.' : 'Saved for this visit. Sign in to keep your favourites across devices.' ?></p></div><a class="sf-text-link" href="products.php">Continue shopping <i class="ph ph-arrow-right"></i></a></header>
<?php if (!gawdee_customer()): ?><p><a class="sf-text-link" href="login.php?return=wishlist.php">Sign in to save your wishlist <i class="ph ph-arrow-right"></i></a></p><?php endif; ?>
<div class="wishlist-grid">
<?php foreach($savedItems as $key=>$ids): $available = array_intersect_key($catalogue,array_flip($ids)); ?>
<section class="wishlist-group" data-saved-group="<?= htmlspecialchars($key) ?>">
<?php if (count($ids)>1 || !$available): ?><header><h2><?= count($ids)>1 ? 'Saved combo' : 'Product unavailable' ?></h2><button type="button" class="sf-text-link" data-wishlist data-product-ids="<?= htmlspecialchars($key) ?>" aria-pressed="true">Remove <i class="ph ph-x"></i></button></header><?php endif; ?>
<?php if (count($available)<count($ids)): ?><p class="sf-empty">Some products are no longer available.</p><?php endif; ?>
<div class="sf-product-grid"><?php foreach ($available as $product) gawdee_card_product($product); ?></div>
</section>
<?php endforeach; ?>
</div>
<div class="sf-empty" data-wishlist-empty <?= $savedItems?'hidden':'' ?>><i class="ph ph-heart" aria-hidden="true"></i><h2>Make room for your favourites.</h2><p>Tap the heart on a product or combo to save it here.</p><a class="sf-button" href="products.php">Explore products <i class="ph ph-arrow-right"></i></a></div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
