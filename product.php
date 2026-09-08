<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$product = product_by_slug($products, $slug);

if ($product === null) {
    http_response_code(404);
    $pageTitle = 'Product not found | Gawdee';
    $pageDescription = 'The requested Gawdee product could not be found.';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="not-found container">
        <span class="eyebrow">404</span>
        <h1>That product has moved.</h1>
        <p>Return to the collection and choose another natural essential.</p>
        <a class="button button--primary" href="index.php#shop">Browse products</a>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['full_name'] . ' | Gawdee';
$pageDescription = $product['description'];
$bodyClass = 'product-page product-page--commerce product-page--reference';
$submittedReviews = gawdee_product_reviews((string) $product['id']);
$baseReviewCount = (int) $product['review_count'];
$reviewCount = $baseReviewCount + count($submittedReviews);
$ratingTotal = (float) $product['rating'] * $baseReviewCount;
foreach ($submittedReviews as $submittedReview) {
    $ratingTotal += (int) $submittedReview['rating'];
}
$rating = $reviewCount > 0 ? $ratingTotal / $reviewCount : (float) $product['rating'];

$sameCategory = [];
$otherProducts = [];
foreach ($products as $candidate) {
    if ($candidate['slug'] === $product['slug']) {
        continue;
    }
    if ($candidate['category_key'] === $product['category_key']) {
        $sameCategory[] = $candidate;
    } else {
        $otherProducts[] = $candidate;
    }
}
$relatedProducts = array_slice(array_merge($sameCategory, $otherProducts), 0, 6);
$variantProducts = array_values(array_filter($products, static fn(array $candidate): bool =>
    ($candidate['family_key'] ?? '') !== '' && ($candidate['family_key'] ?? '') === ($product['family_key'] ?? '')
));
usort($variantProducts, static fn(array $a, array $b): int => (float) ($a['weight'] ?? 0) <=> (float) ($b['weight'] ?? 0));
$gallery = is_array($product['gallery'] ?? null) ? $product['gallery'] : [['src' => $product['image'], 'label' => 'Product view']];
$aPlusImages = is_array($product['aplus_images'] ?? null) ? $product['aplus_images'] : [];
$catalogAssetDirectory = 'assets/images/catalog/' . (string) $product['slug'];
$catalogGallery = [];
$catalogStories = [];
for ($assetIndex = 1; $assetIndex <= 12; $assetIndex++) {
    $assetNumber = str_pad((string) $assetIndex, 2, '0', STR_PAD_LEFT);
    $galleryAsset = $catalogAssetDirectory . '/gallery-' . $assetNumber . '.webp';
    $storyAsset = $catalogAssetDirectory . '/story-' . $assetNumber . '.webp';
    if (is_file(__DIR__ . '/' . $galleryAsset)) {
        $catalogGallery[] = ['src' => $galleryAsset, 'label' => 'Product view ' . $assetIndex];
    }
    if (is_file(__DIR__ . '/' . $storyAsset)) {
        $catalogStories[] = ['src' => $storyAsset, 'label' => 'Product story ' . $assetIndex];
    }
}
if ($catalogGallery && empty($product['has_custom_gallery'])) {
    $gallery = $catalogGallery;
}
if ($catalogStories && !array_key_exists('aplus_images',$product['editor'] ?? [])) {
    $aPlusImages = $catalogStories;
}
$comparisonRows = is_array($product['comparison_rows'] ?? null) ? $product['comparison_rows'] : [];
$comparisonHeadings = is_array($product['comparison_headings'] ?? null) ? $product['comparison_headings'] : [];
$benefits = is_array($product['benefits'] ?? null) ? $product['benefits'] : [];
$ingredients = is_array($product['ingredients'] ?? null) ? $product['ingredients'] : [];
$overviewPoints = is_array($product['overview_points'] ?? null) ? $product['overview_points'] : [];
$usage = is_array($product['usage'] ?? null) ? $product['usage'] : [];
$faqs = is_array($product['faqs'] ?? null) ? $product['faqs'] : [];
$featuredReview = is_array($product['featured_review'] ?? null) ? $product['featured_review'] : null;
$stock = (int) ($product['stock'] ?? 0);
$displayGallery = [];
foreach (array_merge($gallery, $aPlusImages) as $mediaItem) {
    $mediaSource = (string) ($mediaItem['src'] ?? '');
    if ($mediaSource === '' || isset($displayGallery[$mediaSource])) {
        continue;
    }
    $displayGallery[$mediaSource] = [
        'src' => $mediaSource,
        'label' => (string) ($mediaItem['label'] ?? $mediaItem['title'] ?? 'Product view'),
    ];
}
$displayGallery = array_slice(array_values($displayGallery), 0, 5);
$storyMedia = array_slice($aPlusImages ?: $gallery, 0, 6);
if (!$displayGallery) $displayGallery = [['src'=>$product['image'],'label'=>'Product view']];
$nutritionFacts = $product['category_key'] === 'ghee'
    ? [['Energy', '897 kcal'], ['Total Fat', '99.7 g'], ['Saturated Fat', '62.5 g'], ['Trans Fat', '0 g'], ['Cholesterol', '220 mg'], ['Vitamin A', '700 mcg']]
    : [['Pack size', (string) $product['weight']], ['Ingredients', (string) max(1, count($ingredients)) . ' listed'], ['Product type', (string) $product['category']], ['Serving advice', 'See product pack'], ['Storage', 'Cool & dry place']];
$reviewCards = [];
if ($featuredReview) {
    $reviewCards[] = ['name' => (string) $featuredReview['name'], 'rating' => (int) $featuredReview['rating'], 'text' => (string) $featuredReview['text'], 'date' => (string) $featuredReview['date']];
}
foreach ($submittedReviews as $submittedReview) {
    $reviewCards[] = ['name' => (string) $submittedReview['name'], 'rating' => (int) $submittedReview['rating'], 'text' => (string) $submittedReview['review'], 'date' => date('j M Y', strtotime((string) $submittedReview['created_at']))];
}
$reviewCards = array_slice($reviewCards, 0, 6);
$displayReviewCount = $reviewCount;
$displayRating = $reviewCount > 0 ? $rating : 0;
$categoryKey = (string) $product['category_key'];
$productSubtitle = match ($categoryKey) {
    'ghee' => 'Pure & Healthy · Traditional Bilona Method',
    'honey' => 'Raw & Naturally Rich · Seasonal Forest Harvest',
    'nutrition' => 'Wholesome Nutrition · Made for Everyday Families',
    'wellness' => 'Clean Daily Support · Thoughtfully Sourced',
    'sugar' => 'Traditional Sweetness · Everyday Pantry Essential',
    default => 'Pure, authentic goodness for your family',
};
$promiseItems = match ($categoryKey) {
    'ghee' => [
        ['ph-flask', 'Lab Tested', 'For Purity'],
        ['ph-drop', 'No Preservatives', '100% Natural'],
        ['ph-seal-check', 'Bilona Method', 'Traditionally Made'],
        ['ph-plant', 'Farm Fresh', 'From Our Own Farms'],
    ],
    'honey' => [
        ['ph-flask', 'Lab Tested', 'For Purity'],
        ['ph-drop', 'Raw Honey', 'Naturally Rich'],
        ['ph-tree', 'Forest Sourced', 'Seasonal Harvest'],
        ['ph-prohibit', 'No Additives', 'Nothing Unnecessary'],
    ],
    default => [
        ['ph-flask', 'Lab Tested', 'For Purity'],
        ['ph-leaf', 'Naturally Made', 'Clean Ingredients'],
        ['ph-shield-check', 'Quality Checked', 'Every Batch'],
        ['ph-plant', 'Trusted Source', 'Thoughtfully Selected'],
    ],
};
$usesByCategory = [
    'ghee' => [
        ['ph-cooking-pot', 'For Cooking', 'Enhances flavour in everyday meals'],
        ['ph-heart-straight', 'For Health', 'A mindful spoon in a balanced diet'],
        ['ph-baby', 'For Kids', 'Adds familiar richness to family foods'],
        ['ph-cake', 'For Baking', 'Brings aroma and natural richness'],
        ['ph-drop', 'For Skincare', 'Traditionally used in care rituals'],
        ['ph-flower-lotus', 'For Ayurveda', 'Suited to time-honoured practices'],
    ],
    'honey' => [
        ['ph-pancakes', 'For Breakfast', 'Drizzle over toast, fruit and bowls'],
        ['ph-coffee', 'For Drinks', 'Stir into lukewarm beverages'],
        ['ph-bowl-food', 'For Dressings', 'Balances savoury recipes naturally'],
        ['ph-cake', 'For Baking', 'Adds moisture and rounded sweetness'],
        ['ph-heart-straight', 'For Wellness', 'A simple everyday pantry ritual'],
        ['ph-users-three', 'For Family', 'Versatile goodness for the household'],
    ],
    'nutrition' => [
        ['ph-coffee', 'Warm Milk', 'Stirs into a comforting daily drink'],
        ['ph-blender', 'Smoothies', 'Blends into fruit and milk recipes'],
        ['ph-bowl-steam', 'Porridge', 'Adds flavour to warm breakfast bowls'],
        ['ph-cake', 'For Baking', 'Works in wholesome homemade treats'],
        ['ph-baby', 'For Kids', 'A familiar taste for growing routines'],
        ['ph-users-three', 'For Family', 'Made for simple shared moments'],
    ],
    'wellness' => [
        ['ph-blender', 'Smoothies', 'Easy to blend into daily recipes'],
        ['ph-bowl-steam', 'Soups', 'Mix into warm savoury bowls'],
        ['ph-bowl-food', 'Chutneys', 'Adds plant-led flavour and colour'],
        ['ph-cooking-pot', 'Savoury Meals', 'Simple to use in everyday food'],
        ['ph-heart-straight', 'For Wellness', 'Supports a thoughtful routine'],
        ['ph-calendar-check', 'Everyday', 'A consistent pantry companion'],
    ],
    'sugar' => [
        ['ph-coffee', 'Tea & Coffee', 'Dissolves into favourite drinks'],
        ['ph-cookie', 'For Sweets', 'Ideal for traditional homemade treats'],
        ['ph-cake', 'For Baking', 'Reliable sweetness for every bake'],
        ['ph-bowl-food', 'For Desserts', 'Easy to measure and combine'],
        ['ph-cooking-pot', 'For Cooking', 'A versatile kitchen essential'],
        ['ph-calendar-check', 'Everyday', 'Made for familiar family recipes'],
    ],
];
$productUses = array_key_exists('use_cards',$product['editor'] ?? [])
    ? $product['editor']['use_cards']
    : array_map(static fn($step,$index)=>['ph-check-circle','Step '.($index+1),$step],array_slice($usage,0,6),array_keys(array_slice($usage,0,6)));
$benefitCards = array_slice($benefits, 0, 6);
$benefitFallbacks = [
    ['ph-leaf', 'Naturally Made', 'A clean and uncomplicated everyday choice.'],
    ['ph-seal-check', 'Quality Assured', 'Carefully checked before it reaches your home.'],
    ['ph-hand-heart', 'Thoughtfully Crafted', 'Made with care for authentic product character.'],
    ['ph-package', 'Easy to Enjoy', 'Designed to fit naturally into family routines.'],
    ['ph-plant', 'Trusted Source', 'Ingredients selected with care and clarity.'],
    ['ph-sparkle', 'Real Goodness', 'A product experience rooted in simplicity.'],
];
if (!$benefitCards && !array_key_exists('benefits',$product['editor'] ?? [])) $benefitCards = array_slice($benefitFallbacks,0,3);
$farmHeading = match ($categoryKey) {
    'ghee' => 'Where Happy Cows Graze',
    'honey' => 'Where Wild Forests Flourish',
    'nutrition' => 'Where Better Mornings Begin',
    'wellness' => 'Where Nature Meets Everyday Care',
    'sugar' => 'Where Traditional Sweetness Begins',
    default => 'Where Real Goodness Begins',
};
$comparisonTitle = match ($categoryKey) {
    'ghee' => 'Know Your Ghee',
    'honey' => 'Know Your Honey',
    default => 'Know Your Product',
};

$tomorrowImage = $categoryKey === 'ghee' ? 'assets/images/gawdee-a2-farm-hero-v1.png' : ($storyMedia[0]['src'] ?? $displayGallery[0]['src']);
$whyTiles = [
    ['ph-leaf', '100% Natural', 'No unnecessary additives'],
    ['ph-medal', 'Premium Quality', 'Carefully selected batches'],
    ['ph-users-three', 'Trusted by Families', 'Loved across India'],
    ['ph-plant', 'Farm Fresh Sourcing', 'Thoughtful ingredients'],
    ['ph-hand-heart', 'Traditional Wisdom', 'Time-honoured methods'],
    ['ph-prohibit', 'Chemical Free', 'Clean everyday choices'],
    ['ph-seal-check', 'Authentic Preparation', 'Made with patience'],
    ['ph-butterfly', 'Sustainable & Ethical', 'Good for you and nature'],
];
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['full_name'],
    'image' => array_values(array_map(static fn(array $item): string => $item['src'], $gallery)),
    'description' => $product['description'],
    'sku' => $product['sku'],
    'brand' => ['@type' => 'Brand', 'name' => 'Gawdee'],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'INR',
        'price' => (int) $product['price'],
        'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => 'product.php?slug=' . rawurlencode((string) $product['slug']),
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => round($rating, 1),
        'reviewCount' => $reviewCount,
        'bestRating' => 5,
    ],
];
if ($reviewCount === 0) {
    unset($schema['aggregateRating']);
}

$siteDesign = gawdee_site();
$productSubtitle = $product['editor']['subtitle'] ?? $productSubtitle;
if ($productSubtitle === '') $productSubtitle = $product['category'];
$farmHeading = ($product['editor']['story_title'] ?? '') ?: $farmHeading;
$promiseItems = array_map(static fn($p)=>[$p['icon'],$p['title'],$p['subtitle']],gawdee_collections()['product_promises']);
require __DIR__ . '/includes/header.php';
?>

<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<div class="gawdee-pdp" style="--pdp-accent:<?= htmlspecialchars((string) $product['accent']) ?>">
    <div class="container gawdee-pdp__container">
        <nav class="gawdee-pdp__breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a><i class="ph ph-caret-right" aria-hidden="true"></i>
            <a href="products.php?category=<?= rawurlencode($categoryKey) ?>"><?= htmlspecialchars((string) $product['category']) ?></a><i class="ph ph-caret-right" aria-hidden="true"></i>
            <span aria-current="page"><?= htmlspecialchars((string) $product['name']) ?></span>
        </nav>

        <section class="gawdee-pdp-hero" aria-labelledby="product-title">
            <div class="gawdee-pdp-gallery reveal reveal--left">
                <div class="gawdee-pdp-gallery__stage">
                    <span class="gawdee-pdp-gallery__note"><i class="ph ph-leaf"></i> Goodness in its purest form</span>
                    <button type="button" class="gawdee-pdp-gallery__arrow gawdee-pdp-gallery__arrow--prev" data-gallery-prev aria-label="Previous image"><i class="ph ph-caret-left"></i></button>
                    <img src="<?= htmlspecialchars($displayGallery[0]['src']) ?>" alt="<?= htmlspecialchars((string) $product['full_name']) ?>" data-product-main-image>
                    <button type="button" class="gawdee-pdp-gallery__arrow gawdee-pdp-gallery__arrow--next" data-gallery-next aria-label="Next image"><i class="ph ph-caret-right"></i></button>
                    <a class="ref-gallery__expand gawdee-pdp-gallery__expand" href="<?= htmlspecialchars($displayGallery[0]['src']) ?>" target="_blank" rel="noopener" aria-label="Open full-size product image"><i class="ph ph-arrows-out"></i></a>
                </div>
                <div class="gawdee-pdp-gallery__thumbs" aria-label="Product gallery">
                    <?php foreach ($displayGallery as $galleryIndex => $galleryItem): ?>
                        <button type="button" class="gawdee-pdp-gallery__thumb <?= $galleryIndex === 0 ? 'is-active' : '' ?>" data-gallery-thumb data-image="<?= htmlspecialchars($galleryItem['src']) ?>" data-alt="<?= htmlspecialchars((string) $product['full_name'] . ' — ' . $galleryItem['label']) ?>" aria-label="Show <?= htmlspecialchars($galleryItem['label']) ?>" aria-pressed="<?= $galleryIndex === 0 ? 'true' : 'false' ?>">
                            <img src="<?= htmlspecialchars($galleryItem['src']) ?>" alt="" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="gawdee-pdp-buybox reveal">
                <div class="gawdee-pdp-buybox__badges"><span><?= htmlspecialchars((string) ($product['tag'] ?: 'Bestseller')) ?></span><span>100% Pure</span></div>
                <h1 id="product-title"><?= htmlspecialchars((string) $product['full_name']) ?></h1>
                <p class="gawdee-pdp-buybox__subtitle"><?= htmlspecialchars($productSubtitle) ?></p>
                <a class="gawdee-pdp-buybox__rating" href="#reviews"><?php if($displayReviewCount): ?><span aria-hidden="true">★★★★★</span><strong><?= number_format($displayRating,1) ?></strong><small>(<?= $displayReviewCount ?> reviews)</small><?php else: ?><small>No reviews yet · Share your experience</small><?php endif; ?></a>
                <p class="gawdee-pdp-buybox__description"><?= htmlspecialchars((string) $product['description']) ?></p>

                <div class="gawdee-pdp-buybox__price-row">
                    <div class="gawdee-pdp-price"><strong><?= money($product['price']) ?></strong><?php if($product['original_price']>$product['price']): ?><s><?= money($product['original_price']) ?></s><span><?= discount_percentage($product) ?>% OFF</span><?php endif; ?></div>
                    <p class="gawdee-pdp-stock"><b><?= $stock > 0 ? 'In Stock' : 'Out of Stock' ?></b><small>Inclusive of all taxes</small></p>
                </div>

                <div class="gawdee-pdp-variants">
                    <strong><?= count($variantProducts) > 1 ? 'Size: choose your pack' : 'Pack size' ?></strong>
                    <div>
                        <?php foreach ($variantProducts ?: [$product] as $variant): $isCurrent = $variant['slug'] === $product['slug']; ?>
                            <a href="product.php?slug=<?= rawurlencode((string) $variant['slug']) ?>" class="<?= $isCurrent ? 'is-active' : '' ?>" <?= $isCurrent ? 'aria-current="true"' : '' ?>><span><?= htmlspecialchars((string) $variant['weight']) ?></span><b><?= money($variant['price']) ?></b></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="gawdee-pdp-quantity-row">
                    <strong>Quantity</strong>
                    <div class="gawdee-pdp-quantity" aria-label="Quantity selector">
                        <button type="button" data-product-qty-minus aria-label="Decrease quantity"><i class="ph ph-minus"></i></button>
                        <span data-product-qty aria-live="polite">1</span>
                        <button type="button" data-product-qty-plus aria-label="Increase quantity"><i class="ph ph-plus"></i></button>
                    </div>
                </div>

                <div class="gawdee-pdp-actions">
                    <button class="gawdee-pdp-actions__cart product-add" type="button" data-add-to-cart data-id="<?= htmlspecialchars((string) $product['id']) ?>" data-name="<?= htmlspecialchars((string) $product['full_name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= htmlspecialchars((string) $product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>><i class="ph ph-shopping-cart" aria-hidden="true"></i> <?= $stock>0?'Add to Cart':'Sold out' ?></button>
                    <button class="gawdee-pdp-actions__buy" type="button" data-buy-now data-id="<?= htmlspecialchars((string) $product['id']) ?>" data-name="<?= htmlspecialchars((string) $product['full_name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= htmlspecialchars((string) $product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>><i class="ph ph-lightning" aria-hidden="true"></i> Buy Now</button>
                    <button class="gawdee-pdp-actions__wish" type="button" data-wishlist data-product-id="<?= htmlspecialchars($product['id']) ?>" aria-label="Save product" aria-pressed="false"><i class="ph ph-heart"></i></button>
                </div>

                <div class="gawdee-pdp-assurances" aria-label="Purchase assurances">
                    <?php foreach ([['ph-truck','Free Delivery','on orders above ' . money((int) gawdee_setting('free_shipping_threshold', '999'))],['ph-arrows-counter-clockwise','Easy Returns','Hassle-free'],['ph-drop','100% Pure','Lab tested'],['ph-shield-check','Secure Payment','100% safe']] as $assurance): ?>
                        <article><i class="ph <?= $assurance[0] ?>"></i><span><strong><?= $assurance[1] ?></strong><small><?= $assurance[2] ?></small></span></article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="gawdee-pdp-promises" aria-label="Product quality promises">
            <?php foreach ($promiseItems as $promise): ?>
                <article><span><i class="ph <?= htmlspecialchars($promise[0]) ?>" aria-hidden="true"></i></span><div><strong><?= htmlspecialchars($promise[1]) ?></strong><small><?= htmlspecialchars($promise[2]) ?></small></div></article>
            <?php endforeach; ?>
        </section>

        <?php if($siteDesign['pdp_show_story'] === '1'): ?><section class="gawdee-pdp-farm" id="product-story">
            <div class="gawdee-pdp-farm__media"><img src="<?= htmlspecialchars($storyMedia[0]['src'] ?? $displayGallery[0]['src']) ?>" alt="The origin story behind <?= htmlspecialchars((string) $product['name']) ?>" loading="lazy"></div>
            <div class="gawdee-pdp-farm__copy">
                <span>FROM OUR SOURCE TO YOUR HOME</span>
                <h2><?= htmlspecialchars($farmHeading) ?></h2>
                <p><?= htmlspecialchars(($product['editor']['story_text'] ?? '') ?: $siteDesign['pdp_story_text']) ?></p>
                <a href="#goodness">Our sourcing story <i class="ph ph-arrow-right"></i></a>
            </div>
            <em>Good food.<br>Brighter lives.</em>
        </section><?php endif; ?>

        <?php if($siteDesign['pdp_show_highlights'] === '1'): ?><section class="gawdee-pdp-goodness" id="goodness">
            <header class="gawdee-pdp-heading">
                <span>WHAT MAKES IT SPECIAL</span>
                <h2><?= htmlspecialchars($siteDesign['pdp_goodness_title']) ?></h2>
                <p><?= htmlspecialchars($siteDesign['pdp_goodness_text']) ?></p>
            </header>
            <div class="gawdee-pdp-goodness__grid">
                <?php foreach ($benefitCards as $benefitIndex => $benefit): $benefitImage = $storyMedia[$benefitIndex]['src'] ?? $displayGallery[$benefitIndex % count($displayGallery)]['src']; ?>
                    <article class="gawdee-pdp-goodness__card gawdee-pdp-goodness__card--<?= $benefitIndex + 1 ?>">
                        <div><i class="ph <?= htmlspecialchars((string) $benefit[0]) ?>"></i><h3><?= htmlspecialchars((string) $benefit[1]) ?></h3><p><?= htmlspecialchars((string) $benefit[2]) ?></p></div>
                        <img src="<?= htmlspecialchars($benefitImage) ?>" alt="" loading="lazy">
                    </article>
                <?php endforeach; ?>
            </div>
        </section><?php endif; ?>

        <?php if($siteDesign['pdp_show_uses'] === '1'): ?><section class="gawdee-pdp-uses">
            <header class="gawdee-pdp-heading gawdee-pdp-heading--compact"><span>EVERYDAY POSSIBILITIES</span><h2>Uses</h2><p>A spoonful of goodness, a hundred possibilities.</p></header>
            <div class="gawdee-pdp-uses__grid">
                <?php foreach ($productUses as $use): ?>
                    <article><span><i class="ph <?= htmlspecialchars($use[0]) ?>" aria-hidden="true"></i></span><strong><?= htmlspecialchars($use[1]) ?></strong><small><?= htmlspecialchars($use[2]) ?></small></article>
                <?php endforeach; ?>
            </div>
        </section><?php endif; ?>

        <?php if($siteDesign['pdp_show_benefits'] === '1'): ?><section class="gawdee-pdp-benefits">
            <header class="gawdee-pdp-heading gawdee-pdp-heading--compact"><span>ANCIENT WISDOM. MODERN WELLNESS.</span><h2>Key Benefits</h2></header>
            <div class="gawdee-pdp-benefits__grid">
                <?php foreach (array_slice($benefitCards, 0, 4) as $benefit): ?>
                    <article><span><i class="ph <?= htmlspecialchars((string) $benefit[0]) ?>"></i></span><div><strong><?= htmlspecialchars((string) $benefit[1]) ?></strong><small><?= htmlspecialchars((string) $benefit[2]) ?></small></div></article>
                <?php endforeach; ?>
            </div>
        </section><?php endif; ?>


        <section class="sf-product-details" aria-labelledby="product-info-title">
            <header class="gawdee-pdp-heading gawdee-pdp-heading--compact"><h2 id="product-info-title">Product information</h2></header>
            <div class="sf-product-details__grid">
                <?php foreach(['Ingredients'=>$ingredients,'How to use'=>$usage,'Good to know'=>$overviewPoints] as $label=>$points): if(!$points) continue; ?>
                <article><h3><?= $label ?></h3><ul><?php foreach($points as $point): ?><li><?= htmlspecialchars((string)$point) ?></li><?php endforeach; ?></ul></article>
                <?php endforeach; ?>
                <?php if($product['storage'] ?? ''): ?><article><h3>Storage</h3><p><?= htmlspecialchars($product['storage']) ?></p></article><?php endif; ?>
            </div>
        </section>

        <?php if($siteDesign['pdp_show_faq'] === '1'): ?><section class="gawdee-pdp-faq" id="faqs">
            <div class="gawdee-pdp-faq__intro">
                <img src="<?= htmlspecialchars($displayGallery[min(2, count($displayGallery) - 1)]['src']) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?> serving suggestion" loading="lazy">
                <div><span>REAL ANSWERS, CLEARLY SHARED</span><h2>You Ask,<br>We Answer.</h2><p>Everything you need to know about <?= htmlspecialchars((string) $product['name']) ?>.</p></div>
            </div>
            <div class="gawdee-pdp-faq__list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details <?= $index === 0 ? 'open' : '' ?>><summary><?= htmlspecialchars((string) $faq['question']) ?><i class="ph ph-plus"></i></summary><p><?= htmlspecialchars((string) $faq['answer']) ?></p></details>
                <?php endforeach; ?>
            </div>
        </section><?php endif; ?>

        <?php if($comparisonRows && $siteDesign['pdp_show_comparison'] === '1'): ?><section class="gawdee-pdp-comparison">
            <header class="gawdee-pdp-heading gawdee-pdp-heading--compact"><span>A CLEAR DIFFERENCE YOU CAN TRUST</span><h2><?= htmlspecialchars($comparisonTitle) ?></h2></header>
            <div class="gawdee-pdp-comparison__body">
                <div class="gawdee-pdp-comparison__product"><img src="<?= htmlspecialchars((string) $product['image']) ?>" alt="<?= htmlspecialchars((string) $product['full_name']) ?>" loading="lazy"><strong><?= htmlspecialchars((string) ($comparisonHeadings[0] ?? ('Gawdee ' . $product['name']))) ?></strong></div>
                <div class="gawdee-pdp-comparison__list gawdee-pdp-comparison__list--good">
                    <?php foreach ($comparisonRows as $comparisonRow): $values = array_values(is_array($comparisonRow) ? $comparisonRow : [$comparisonRow]); ?><p><i class="ph-fill ph-check-circle"></i><?= htmlspecialchars((string) ($values[0] ?? 'Carefully made')) ?></p><?php endforeach; ?>
                </div>
                <span class="gawdee-pdp-comparison__vs">VS</span>
                <div class="gawdee-pdp-comparison__alternative"><i class="ph ph-jar"></i><strong><?= htmlspecialchars((string) ($comparisonHeadings[1] ?? 'Ordinary alternatives')) ?></strong></div>
                <div class="gawdee-pdp-comparison__list gawdee-pdp-comparison__list--ordinary">
                    <?php foreach ($comparisonRows as $comparisonRow): $values = array_values(is_array($comparisonRow) ? $comparisonRow : [$comparisonRow]); ?><p><i class="ph ph-x"></i><?= htmlspecialchars((string) ($values[1] ?? 'May be less transparent')) ?></p><?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php endif; ?>
        <?php if($siteDesign['pdp_show_reviews'] === '1'): ?><section class="gawdee-pdp-reviews" id="reviews">
            <header><div><span>TRUSTED BY FAMILIES ACROSS INDIA</span><h2><?= htmlspecialchars($siteDesign['pdp_reviews_title']) ?></h2></div><a href="#write-review">Write a Review <i class="ph ph-arrow-right"></i></a></header>
            <?php if(!$reviewCards): ?><p class="sf-empty">No customer reviews yet. Be the first to share your experience.</p><?php endif; ?>
            <div class="gawdee-pdp-reviews__grid" data-review-list>
                <?php foreach ($reviewCards as $review): ?>
                    <article><div class="gawdee-pdp-reviews__person"><b><?= htmlspecialchars(strtoupper(substr((string) $review['name'], 0, 1))) ?></b><p><strong><?= htmlspecialchars((string) $review['name']) ?></strong><small>Customer review</small></p></div><span class="gawdee-pdp-reviews__stars"><?= str_repeat('★', (int) $review['rating']) ?></span><blockquote>“<?= htmlspecialchars((string) $review['text']) ?>”</blockquote><time><?= htmlspecialchars((string) $review['date']) ?></time></article>
                <?php endforeach; ?>
            </div>
            <details class="gawdee-pdp-review-form" id="write-review">
                <summary>Share your experience <i class="ph ph-plus"></i></summary>
                <form data-review-form data-product-id="<?= htmlspecialchars((string) $product['id']) ?>">
                    <div class="gawdee-pdp-review-form__rating"><?php for ($star = 5; $star >= 1; $star--): ?><input type="radio" id="pdp-rating-<?= $star ?>" name="rating" value="<?= $star ?>" <?= $star === 5 ? 'required' : '' ?>><label for="pdp-rating-<?= $star ?>" aria-label="<?= $star ?> stars">★</label><?php endfor; ?></div>
                    <input name="name" placeholder="Your name" aria-label="Your name" autocomplete="name" required><input type="email" name="email" placeholder="Email address" aria-label="Email address" autocomplete="email" required><textarea aria-label="Your review" name="review" minlength="15" maxlength="1200" placeholder="Tell other families what you liked" required></textarea><button type="submit">Submit Review</button><p data-review-status aria-live="polite"></p>
                </form>
            </details>
        </section><?php endif; ?>

        <?php if($siteDesign['pdp_show_related'] === '1'): ?><section class="gawdee-pdp-related">
            <header><div><span>EXPLORE MORE WHOLESOME PRODUCTS</span><h2>You May Also Like</h2></div><a href="products.php">View All Products <i class="ph ph-arrow-right"></i></a></header>
            <div class="gawdee-pdp-related__rail" id="pdp-related-products" data-sliding-rail tabindex="0">
                <?php foreach ($relatedProducts as $related): ?>
                    <article>
                        <a href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>" class="gawdee-pdp-related__image"><img src="<?= htmlspecialchars((string) $related['image']) ?>" alt="<?= htmlspecialchars((string) $related['full_name']) ?>" loading="lazy"></a>
                        <div><small><?= htmlspecialchars((string) $related['weight']) ?></small><h3><a href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>"><?= htmlspecialchars((string) $related['name']) ?></a></h3><p><strong><?= money($related['price']) ?></strong><s><?= money($related['original_price']) ?></s></p><button type="button" data-add-to-cart data-id="<?= htmlspecialchars((string) $related['id']) ?>" data-name="<?= htmlspecialchars((string) $related['full_name']) ?>" data-price="<?= (int) $related['price'] ?>" data-image="<?= htmlspecialchars((string) $related['image']) ?>" <?= $related['stock'] <= 0 ? 'disabled' : '' ?>><?= $related['stock'] > 0 ? 'Add' : 'Sold out' ?> <i class="ph ph-shopping-cart"></i></button></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section><?php endif; ?>

        <?php if($siteDesign['pdp_show_tomorrow'] === '1'): ?><section class="gawdee-pdp-tomorrow">
            <div class="gawdee-pdp-tomorrow__image"><img src="<?= htmlspecialchars($tomorrowImage) ?>" alt="Gawdee's farm-led promise" loading="lazy"></div>
            <div class="gawdee-pdp-tomorrow__copy"><span>OUR PROMISE CONTINUES</span><h2><?= htmlspecialchars($siteDesign['pdp_tomorrow_title']) ?></h2><p><?= htmlspecialchars($siteDesign['pdp_tomorrow_text']) ?></p><a class="sf-text-link" href="index.php#quality-promise">Our quality promise <i class="ph ph-arrow-right"></i></a></div>
        </section><?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
