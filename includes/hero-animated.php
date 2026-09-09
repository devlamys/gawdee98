<?php
declare(strict_types=1);
/**
 * Temporary animated hero — exact copy port from demo animated_carousel/index.html (Style 01).
 * Toggle: $useAnimatedHero in index.php. Delete this file + css/js to revert.
 * Images: assets/images/hero-animated/*.png (copied from demo).
 */

// Same copy as the demo. URLs map to the closest real product/category pages.
$gxHeroSlides = [
    [
        'cat' => 'A2 Vedic • Grass-Fed',
        'title' => 'A2 Vedic<br><span>Gir Cow Ghee</span>',
        'word' => 'GHEE',
        'sub' => 'Pure & Healthy Gir Cow A2 Ghee hand-churned using traditional Bilona method. Nutty, aromatic & nourishing.',
        'priceLabel' => '₹899', 'mrpLabel' => '₹1,099', 'off' => 'Save 18%',
        'reviews' => '4.9 — 2,340 rituals',
        'img' => 'assets/images/hero-animated/ghee.png',
        'alt' => 'GAWDEE Pure Gir Cow A2 Bilona Ghee Jar',
        'url' => 'product.php?slug=gawdee-gir-cow-a2-ghee-500-ml',
        'cartId' => 'ghee-500', 'cartName' => 'Gawdee Gir Cow A2 Ghee 500ml',
        'cartPrice' => 891, 'cartImage' => 'assets/images/products/ghee-500.webp',
    ],
    [
        'cat' => '100% Natural • Homemade Taste',
        'title' => 'MixMe Powder<br><span>Vanilla Flavour</span>',
        'word' => 'MIXME',
        'sub' => 'Nutritive food powder for kids (2+ yrs) & adults. Packed with Ashwagandha, Shatavari, Brahmi, Peanut & Dates.',
        'priceLabel' => '₹649', 'mrpLabel' => '₹799', 'off' => 'Save 19%',
        'reviews' => '4.8 — 1,870 rituals',
        'img' => 'assets/images/hero-animated/mixme-vanilla.png',
        'alt' => 'GAWDEE MixMe Nutritive Food Powder Vanilla Flavour Pouch',
        'url' => 'product.php?slug=gawdee-mixme-choco-500-g',
        'cartId' => 'mixme-choco', 'cartName' => 'Gawdee MixMe — Choco 500g',
        'cartPrice' => 759, 'cartImage' => 'assets/images/products/mixme-choco.webp',
    ],
    [
        'cat' => '100% Natural • Homemade Taste',
        'title' => 'MixMe Powder<br><span>Cardamom Flavour</span>',
        'word' => 'MIXME',
        'sub' => 'Nutritive food powder blend with Vavding, Ganthoda, Brahmi & Shankhpushpi in soothing Cardamom flavour.',
        'priceLabel' => '₹649', 'mrpLabel' => '₹799', 'off' => 'Save 19%',
        'reviews' => '4.9 — 2,110 rituals',
        'img' => 'assets/images/hero-animated/mixme-cardamom.png',
        'alt' => 'GAWDEE MixMe Nutritive Food Powder Cardamom Flavour Pouch',
        'url' => 'product.php?slug=gawdee-mixme-elaichi-500-g',
        'cartId' => 'mixme-elaichi', 'cartName' => 'Gawdee MixMe — Elaichi 500g',
        'cartPrice' => 759, 'cartImage' => 'assets/images/products/mixme-elaichi.webp',
    ],
    [
        'cat' => 'Traditional • Unrefined Sweetness',
        'title' => 'Organic Jaggery<br><span>Fine Powder 1kg</span>',
        'word' => 'JAGGERY',
        'sub' => 'Naturally processed with zero chemical processing. Fine powder texture for tea, milk, sweets, laddoo & halwa.',
        'priceLabel' => '₹299', 'mrpLabel' => '₹399', 'off' => 'Save 25%',
        'reviews' => '4.9 — 3,102 rituals',
        'img' => 'assets/images/hero-animated/jaggery.png',
        'alt' => 'GAWDEE Organic Jaggery Powder 1kg Pouch',
        'url' => 'products.php?category=sugar',
        'cartId' => 'burra-sugar', 'cartName' => 'Gawdee Burra Sugar 1kg',
        'cartPrice' => 159, 'cartImage' => 'assets/images/products/burra-sugar.webp',
    ],
    [
        'cat' => 'Authentic Nasya • Belly Button Drops',
        'title' => 'Taral Drop<br><span>(Nasya) 30ml</span>',
        'word' => 'TARAL',
        'sub' => 'Authentic organic nutrition drops for nose & belly button. Boosts clarity, breath & natural wellness.',
        'priceLabel' => '₹399', 'mrpLabel' => '₹499', 'off' => 'Save 20%',
        'reviews' => '4.7 — 940 rituals',
        'img' => 'assets/images/hero-animated/taral.png',
        'alt' => 'GAWDEE Taral Drop Nasya Bottle 30ml',
        'url' => 'product.php?slug=gawdee-taral-drop-30-ml',
        'cartId' => 'taral-drop', 'cartName' => 'Gawdee Taral Drop 30ml',
        'cartPrice' => 209, 'cartImage' => 'assets/images/products/taral-drop.webp',
    ],
];
$gxFirst = $gxHeroSlides[0];
$gxCssV = (int) @filemtime(__DIR__ . '/../assets/css/hero-animated.css');
$gxJsV = (int) @filemtime(__DIR__ . '/../assets/js/hero-animated.js');
?>
<link rel="stylesheet" href="assets/css/hero-animated.css?v=<?= $gxCssV ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script type="application/json" id="gxHeroData"><?= json_encode($gxHeroSlides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>

<section class="gx-hero grain" aria-label="Featured organic products">
    <div class="gx-hero__bg" aria-hidden="true">
        <div class="gx-hero__bg-left"></div>
        <div class="gx-hero__bg-right"></div>
    </div>
    <div class="gx-hero__glow gx-hero__glow--left" aria-hidden="true"></div>
    <div class="gx-hero__glow gx-hero__glow--right" aria-hidden="true"></div>
    <div class="gx-hero__word" id="gxWord" aria-hidden="true"><?= htmlspecialchars($gxFirst['word']) ?></div>

    <div class="gx-hero__grid">
        <div class="gx-hero__copy">
            <div class="gx-hero__kicker gx-intro">
                <span class="gx-hero__kicker-line" aria-hidden="true"></span>
                <span class="gx-hero__pill" id="gxCat"><?= htmlspecialchars($gxFirst['cat']) ?></span>
                <span class="gx-hero__count" id="gxCount">01 — 05</span>
            </div>

            <div id="gxText">
                <h1 class="gx-hero__title" id="gxTitle"><?= $gxFirst['title'] ?></h1>
                <p class="gx-hero__sub" id="gxSub"><?= htmlspecialchars($gxFirst['sub']) ?></p>
                <div class="gx-hero__rating">
                    <span class="gx-hero__stars" aria-label="Rated 4.9 out of 5 stars">★★★★★</span>
                    <span class="gx-hero__reviews" id="gxReviews"><?= htmlspecialchars($gxFirst['reviews']) ?></span>
                </div>
                <div class="gx-hero__price-row">
                    <span class="gx-hero__price" id="gxPrice"><?= htmlspecialchars($gxFirst['priceLabel']) ?></span>
                    <span class="gx-hero__mrp" id="gxMrp"><?= htmlspecialchars($gxFirst['mrpLabel']) ?></span>
                    <span class="gx-hero__off" id="gxOff"><?= htmlspecialchars($gxFirst['off']) ?></span>
                </div>
            </div>

            <div class="gx-hero__cta gx-intro">
                <a class="gx-btn-lux" id="gxShopBtn" href="<?= htmlspecialchars($gxFirst['url']) ?>">
                    Shop Now <span class="gx-btn-lux__arrow">→</span>
                </a>
                <button type="button" class="gx-btn-cart" id="gxCartBtn"
                    data-add-to-cart
                    data-id="<?= htmlspecialchars($gxFirst['cartId']) ?>"
                    data-name="<?= htmlspecialchars($gxFirst['cartName']) ?>"
                    data-price="<?= (int) $gxFirst['cartPrice'] ?>"
                    data-image="<?= htmlspecialchars($gxFirst['cartImage']) ?>">
                    Add to Cart • <span class="gx-mini"><?= htmlspecialchars($gxFirst['priceLabel']) ?></span>
                </button>
            </div>

            <div class="gx-hero__controls gx-intro">
                <div class="gx-hero__arrows">
                    <button type="button" class="gx-arrow gx-arrow--ghost" id="gxPrev" aria-label="Previous product">←</button>
                    <button type="button" class="gx-arrow gx-arrow--solid" id="gxNext" aria-label="Next product">→</button>
                </div>
                <div class="gx-hero__dots" id="gxDots"></div>
                <div class="gx-hero__progress" aria-hidden="true"><div id="gxProgressBar"></div></div>
            </div>

            <div class="gx-hero__trust gx-intro">
                <span>✦ Free shipping over ₹999</span>
                <span>✦ Lab-tested purity</span>
                <span>✦ COD available</span>
            </div>
        </div>

        <div class="gx-hero__stage-wrap">
            <div class="gx-hero__next gx-glass">
                <small>Next up</small>
                <strong id="gxNextName">Mixme</strong>
            </div>
            <div class="gx-stage" id="gxStage">
                <div class="gx-stage__ring" aria-hidden="true"></div>
                <div class="gx-stage__halo" aria-hidden="true"></div>
                <div class="gx-stage__circle" aria-hidden="true"></div>
                <div class="gx-shadow" id="gxShadow" aria-hidden="true"></div>
            </div>
            <div class="gx-hero__badge gx-glass">
                <b id="gxIndex">01</b>
                <span class="gx-hero__badge-sep" aria-hidden="true"></span>
                <small>Organic<br>Certified</small>
            </div>
        </div>
    </div>

    <div class="gx-hero__marquee" aria-hidden="true">
        <div class="gx-marquee__viewport">
            <div class="gx-marquee__track">
                <span>✦ A2 Bilona Ghee</span><span>✦ MixMe Nutritive Blend</span>
                <span>✦ Organic Jaggery</span><span>✦ Taral Nasya Drop</span>
                <span>✦ No Refined Sugar</span><span>✦ Farm Fresh Purity</span>
                <span>✦ A2 Bilona Ghee</span><span>✦ MixMe Nutritive Blend</span>
                <span>✦ Organic Jaggery</span><span>✦ Taral Nasya Drop</span>
                <span>✦ No Refined Sugar</span><span>✦ Farm Fresh Purity</span>
            </div>
        </div>
    </div>
</section>
<script src="assets/js/hero-animated.js?v=<?= $gxJsV ?>" defer></script>
