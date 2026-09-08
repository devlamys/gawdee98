<?php

declare(strict_types=1);

require_once __DIR__ . '/storefront.php';
$siteDesign = gawdee_site();
$siteCollections = gawdee_collections();

$pageTitle = $pageTitle ?? 'Gawdee — Pure food, thoughtfully made';
$pageDescription = $pageDescription ?? 'Traditional foods and natural wellness essentials, thoughtfully sourced and made for modern families.';
$bodyClass = $bodyClass ?? '';
$headerCustomer = gawdee_customer();
$isReferenceProductPage = str_contains($bodyClass, 'product-page--reference');
$isCommerceHome = str_contains($bodyClass, 'commerce-home');
$fontStacks = [
    'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif',
    'arial' => 'Arial, Helvetica, sans-serif',
    'dm-sans' => '"DM Sans", Arial, sans-serif',
];
$siteBodyFont = $fontStacks[gawdee_setting('site_body_font', 'system')] ?? $fontStacks['system'];
$siteHeadingFont = $fontStacks[gawdee_setting('site_heading_font', 'system')] ?? $fontStacks['system'];
$siteBaseFontSize = min(20, max(14, (int) gawdee_setting('site_base_font_size', '16')));
$styleVersion = (string) max(
    (int) @filemtime(__DIR__ . '/../assets/css/style.css'),
    (int) @filemtime(__DIR__ . '/../assets/css/gawdee-reference.css'),
    (int) @filemtime(__DIR__ . '/../assets/css/storefront-modern.css')
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#009d8a">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="gawdee-csrf" content="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600&family=DM+Sans:wght@400;500;600;700&family=Lora:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/duotone/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= rawurlencode($styleVersion) ?>">
    <link rel="stylesheet" href="assets/css/gawdee-reference.css?v=<?= rawurlencode($styleVersion) ?>">
    <link rel="stylesheet" href="assets/css/storefront-modern.css?v=<?= rawurlencode($styleVersion) ?>">
    <link rel="stylesheet" href="assets/css/reference-sections.css?v=<?= (int)@filemtime(__DIR__.'/../assets/css/reference-sections.css') ?>">
    <style>:root{--sf-green:<?= htmlspecialchars($siteDesign['brand_color']) ?>;--sf-accent:<?= htmlspecialchars($siteDesign['brand_accent']) ?>;--site-body-font:<?= $siteBodyFont ?>;--site-heading-font:<?= $siteHeadingFont ?>;--site-base-font-size:<?= $siteBaseFontSize ?>px}</style>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?> sf-modern" data-density="<?= htmlspecialchars($siteDesign['site_density']) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>

<?php if($isCommerceHome && $siteDesign['header_benefit_strip'] === '1'): ?>
<div class="sf-reference-promo" aria-label="The Gawdee promise"><ul><?php foreach($siteCollections['header_benefits'] as $benefit): ?><li><i class="ph <?= htmlspecialchars($benefit['icon']) ?>" aria-hidden="true"></i><span><?= htmlspecialchars(str_replace('{threshold}',number_format((int)gawdee_setting('free_shipping_threshold','999')),$benefit['title'])) ?></span></li><?php endforeach; ?></ul><span class="sf-reference-promo__tagline"><?= htmlspecialchars($siteDesign['brand_tagline']) ?></span></div>
<?php else: ?><div class="promo-strip sf-announcement"><a href="<?= htmlspecialchars(gawdee_public_url($siteDesign['announcement_url'],'products.php')) ?>"><i class="ph ph-leaf" aria-hidden="true"></i><?= htmlspecialchars(str_replace('{threshold}', number_format((int)gawdee_setting('free_shipping_threshold','999')), $siteDesign['announcement_text'])) ?></a><span><?= htmlspecialchars($siteDesign['brand_tagline']) ?></span></div><?php endif; ?>

<header class="commerce-header" data-header>
    <div class="commerce-header__float">
        <button class="commerce-icon mobile-menu-toggle mobile-only" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false">
            <i class="ph ph-list" aria-hidden="true"></i>
        </button>

        <a class="commerce-logo" href="index.php" aria-label="Gawdee home">
            <img src="<?= htmlspecialchars(gawdee_public_url($siteDesign['brand_logo'],'assets/images/logo.png')) ?>" alt="<?= htmlspecialchars($siteDesign['brand_name']) ?>">
        </a>

        <nav class="commerce-nav reference-nav-shell" aria-label="Main navigation">
            <?php foreach ($siteCollections['navigation'] as $nav):
                $navCategory = str_contains($nav['url'],'category=');
                if($navCategory): ?><div class="sf-nav-group"><a href="<?= htmlspecialchars(gawdee_public_url($nav['url'],'products.php')) ?>"><?= htmlspecialchars($nav['title']) ?></a><button type="button" data-nav-disclosure aria-expanded="false" aria-label="More <?= htmlspecialchars($nav['title']) ?> links"><i class="ph ph-caret-down" aria-hidden="true"></i></button><div class="sf-nav-dropdown" hidden><a href="<?= htmlspecialchars(gawdee_public_url($nav['url'],'products.php')) ?>">Shop <?= htmlspecialchars($nav['title']) ?> <i class="ph ph-arrow-right"></i></a><a href="index.php#offers">Explore Combo Deals</a><a href="index.php#quality-promise">Our Quality Promise</a></div></div><?php else: ?><a href="<?= htmlspecialchars(gawdee_public_url($nav['url'],'products.php')) ?>" <?= $isCommerceHome && $nav['url'] === 'index.php' ? 'class="is-active" aria-current="page"' : '' ?>><?= htmlspecialchars($nav['title']) ?></a><?php endif; endforeach; ?>
        </nav>

        <div class="commerce-actions">
            <button class="commerce-action commerce-action--search" type="button" data-search-toggle aria-label="Search products">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            </button>
            <a class="commerce-action desktop-only" href="<?= $headerCustomer ? 'account.php' : 'login.php' ?>" aria-label="<?= $headerCustomer ? 'Open account' : 'Sign in' ?>">
                <i class="ph <?= $headerCustomer ? 'ph-user-circle-check' : 'ph-user' ?>" aria-hidden="true"></i>
            </a>
            <a class="commerce-action desktop-only" href="wishlist.php" aria-label="Open saved products"><span class="commerce-action__icon"><i class="ph ph-heart" aria-hidden="true"></i><b data-wishlist-count>0</b></span></a>
            <button class="commerce-action" type="button" data-cart-toggle aria-label="Open shopping bag">
                <span class="commerce-action__icon"><i class="ph ph-shopping-cart" aria-hidden="true"></i><b data-cart-count>0</b></span>
            </button>
            <a class="commerce-shop-now desktop-only" href="products.php"><?= htmlspecialchars($siteDesign['header_shop_label']) ?> <i class="ph ph-arrow-right"></i></a>
        </div>

        <div class="commerce-search-panel" data-search-panel>
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="site-search">Search products and categories</label>
            <input id="site-search" type="search" value="<?= htmlspecialchars($headerSearchValue ?? '') ?>" placeholder="Search for A2 Ghee, raw honey, natural foods…" autocomplete="off" data-site-search>
            <button type="button" data-search-close aria-label="Close search"><i class="ph ph-x"></i></button>
        </div>
    </div>

    <nav class="mobile-nav commerce-mobile-nav" data-mobile-menu aria-label="Mobile navigation">
        <?php foreach ($siteCollections['navigation'] as $nav): ?><a href="<?= htmlspecialchars(gawdee_public_url($nav['url'],'products.php')) ?>"><?= htmlspecialchars($nav['title']) ?><i class="ph ph-arrow-right" aria-hidden="true"></i></a><?php endforeach; ?>
        <a href="wishlist.php">Saved products <i class="ph ph-heart" aria-hidden="true"></i></a>
        <a href="<?= $headerCustomer ? 'account.php' : 'login.php' ?>"><?= $headerCustomer ? 'My account & orders' : 'Sign in / register' ?><i class="ph ph-user" aria-hidden="true"></i></a>
    </nav>
</header>

<div class="drawer-backdrop" data-drawer-backdrop></div>
<aside class="cart-drawer" role="dialog" aria-modal="true" data-cart-drawer aria-labelledby="cart-title" aria-hidden="true" inert>
    <div class="cart-drawer__header">
        <div><span class="eyebrow">Your selection</span><h2 id="cart-title">Shopping bag</h2></div>
        <button class="icon-button" type="button" data-cart-close aria-label="Close shopping bag"><i class="ph ph-x"></i></button>
    </div>
    <div class="cart-items" data-cart-items></div>
    <div class="cart-empty" data-cart-empty>
        <i class="ph ph-shopping-bag-open" aria-hidden="true"></i>
        <h3>Your bag is waiting</h3>
        <p>Add a few natural essentials and they’ll appear here.</p>
        <button class="button button--secondary" type="button" data-cart-close>Continue shopping</button>
    </div>
    <div class="cart-summary" data-cart-summary hidden>
        <div class="cart-summary__line"><span>Subtotal</span><strong data-cart-total>₹0</strong></div>
        <p>Taxes and delivery are calculated at checkout.</p>
        <a class="button button--primary button--full" href="checkout.php">Secure checkout <i class="ph ph-arrow-right"></i></a>
    </div>
</aside>

<main id="main-content">
