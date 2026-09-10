<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/data.php';
$pageTitle = 'Gawdee — Pure by Nature. Trusted for Generations.';
$pageDescription = 'A2 Gir cow ghee, raw honey, daily nutrition and pantry essentials from Gawdee.';
$bodyClass = 'commerce-home gawdee-reference-home sf-home';
$homepageSections = gawdee_sections();
$collections = gawdee_collections();
$activeProducts = array_column($products, null, 'id');
$homeTitle = static function (array $section, string $id, string $fallback = ''): void { ?>
    <header class="sf-section-heading">
        <div><?php if ($section['eyebrow']): ?><span
                    class="sf-eyebrow"><?= htmlspecialchars($section['eyebrow']) ?></span><?php endif; ?>
            <h2 id="<?= $id ?>"><?= gawdee_heading($section['title'] ?: $fallback) ?></h2>
            <?php if ($section['subtitle']): ?>
                <p><?= htmlspecialchars($section['subtitle']) ?></p><?php endif; ?>
        </div><?php if ($section['button_label'] && $section['button_url']): ?><a class="sf-text-link"
                href="<?= htmlspecialchars(gawdee_public_url($section['button_url'], 'products.php')) ?>"><?= htmlspecialchars($section['button_label']) ?>
                <i class="ph ph-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
    </header>
<?php };
$embedVideo = static function (string $url): string {
    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = trim((string) ($parts['path'] ?? ''), '/');
    if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        parse_str($parts['query'] ?? '', $query);
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($query['v'] ?? ''));
        return $id ? 'https://www.youtube.com/embed/' . $id : '';
    }
    if ($host === 'youtu.be')
        return 'https://www.youtube.com/embed/' . preg_replace('/[^A-Za-z0-9_-]/', '', $path);
    if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true))
        return 'https://player.vimeo.com/video/' . preg_replace('/[^0-9]/', '', $path);
    return '';
};
require __DIR__ . '/includes/header.php';
// TEMPORARY TRIAL: animated hero ported from demo animated_carousel/index.html (Style 01).
// Set to false to restore the original banner/poster hero instantly.
$useAnimatedHero = true;
// TEMPORARY TRIAL: compact inline trust/values design (same icon/title/subtitle).
// Set to false to restore the original .sf-values design instantly.
$useNewValuesDesign = true;
foreach ($homepageSections as $key => $section):
    if (!(int) $section['is_active'] && !($key === 'hero' && $useAnimatedHero))
        continue;
    switch ($key):
        case 'hero':
            if ($useAnimatedHero) {
                require __DIR__ . '/includes/hero-animated.php';
                break;
            }
            $heroBanners = gawdee_site()['home_hero_slideshow'] === '1' ? gawdee_banners() : [];
            if ($heroBanners): ?>
                <section class="sf-hero" data-hero-slider aria-label="Featured collections">
                    <div class="hero-track" data-hero-track><?php foreach ($heroBanners as $slideIndex => $banner): ?><a data-hero-slide
                                class="<?= $slideIndex === 0 ? 'is-active' : '' ?>"
                                href="<?= htmlspecialchars(gawdee_public_url($banner['link_url'], 'products.php')) ?>"
                                aria-label="<?= htmlspecialchars($banner['title']) ?>"
                                aria-hidden="<?= $slideIndex === 0 ? 'false' : 'true' ?>" tabindex="<?= $slideIndex === 0 ? '0' : '-1' ?>">
                                <picture><?php if ($banner['mobile_image']): ?>
                                        <source media="(max-width:600px)"
                                            srcset="<?= htmlspecialchars(gawdee_public_url($banner['mobile_image'])) ?>"><?php endif; ?><img
                                        src="<?= htmlspecialchars(gawdee_public_url($banner['desktop_image'])) ?>"
                                        alt="<?= htmlspecialchars($banner['alt_text'] ?: $banner['title']) ?>" <?= $slideIndex === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                                </picture>
                            </a><?php endforeach; ?></div>
                    <?php if (count($heroBanners) > 1): ?>
                        <div class="sf-hero__dots" role="tablist" aria-label="Choose a banner">
                            <?php foreach ($heroBanners as $slideIndex => $banner): ?><button type="button" role="tab"
                                    data-hero-dot="<?= $slideIndex ?>" class="<?= $slideIndex === 0 ? 'is-active' : '' ?>"
                                    aria-selected="<?= $slideIndex === 0 ? 'true' : 'false' ?>"
                                    aria-label="<?= htmlspecialchars($banner['title']) ?>">
                                </button><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="sf-hero gawdee-hero--poster" aria-label="<?= htmlspecialchars($section['title']) ?>">
                    <a href="<?= htmlspecialchars(gawdee_public_url($section['button_url'], 'products.php')) ?>"
                        aria-label="<?= htmlspecialchars($section['button_label'] ?: 'Shop Gawdee products') ?>">
                        <picture><?php if ($section['mobile_image']): ?>
                                <source media="(max-width:600px)"
                                    srcset="<?= htmlspecialchars(gawdee_public_url($section['mobile_image'])) ?>"><?php endif; ?><img
                                src="<?= htmlspecialchars(gawdee_public_url($section['image'], 'assets/images/gawdee-reference-poster-hero-v3.png')) ?>"
                                alt="<?= htmlspecialchars($section['title']) ?>" width="1672" height="941" fetchpriority="high">
                        </picture>
                    </a>
                </section>
            <?php endif; ?>
            <?php break;
        case 'benefits':
        case 'why':
            $items = $collections[$key === 'benefits' ? 'trust' : 'why_choose'];
            if (!$items)
                break; ?>
            <section class="sf-section sf-reference <?= $key === 'benefits' ? 'sf-trust-strip' : 'sf-why' ?>" id="<?= $key ?>"
                aria-labelledby="title-<?= $key ?>">
                <div class="sf-container">
                    <?php if ($key !== 'benefits'):
                        $homeTitle($section, 'title-' . $key);
                    else: ?>
                        <h2 class="sr-only" id="title-<?= $key ?>"><?= htmlspecialchars($section['title']) ?></h2><?php endif; ?>
                    <?php if (!empty($useNewValuesDesign)): ?>
                    <div class="gx-values">
                        <?php foreach ($items as $item): ?>
                            <article class="gx-value">
                                <span class="gx-value-icon"><?php if ($item['image']):
                                    gawdee_artwork($item['image'], $item['image_crop'] ?? '');
                                else: ?><i class="ph <?= htmlspecialchars($item['icon']) ?>"
                                            aria-hidden="true"></i><?php endif; ?></span>
                                <span class="gx-value-text">
                                    <h3><?= nl2br(htmlspecialchars($item['title'])) ?></h3>
                                    <p><?= htmlspecialchars($item['subtitle']) ?></p>
                                </span>
                            </article><?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="sf-values">
                        <?php foreach ($items as $item): ?>
                            <article>
                                <span class="sf-value-icon"><?php if ($item['image']):
                                    gawdee_artwork($item['image'], $item['image_crop'] ?? '');
                                else: ?><i class="ph <?= htmlspecialchars($item['icon']) ?>"
                                            aria-hidden="true"></i><?php endif; ?></span>
                                <h3><?= nl2br(htmlspecialchars($item['title'])) ?></h3>
                                <p><?= htmlspecialchars($item['subtitle']) ?></p>
                            </article><?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php break;
        case 'process':
            $items = gawdee_section_items($key);
            if (!$items)
                break; ?>
            <section class="sf-section" id="farms" aria-labelledby="title-farms">
                <div class="sf-container"><?php $homeTitle($section, 'title-farms'); ?>
                    <?php if (!empty($useNewValuesDesign)): ?>
                    <div class="gx-values">
                        <?php foreach ($items as $item): ?>
                            <article class="gx-value">
                                <span class="gx-value-icon"><i class="ph <?= htmlspecialchars($item['icon']) ?>"
                                            aria-hidden="true"></i></span>
                                <span class="gx-value-text">
                                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                                    <p><?= htmlspecialchars($item['subtitle']) ?></p>
                                </span>
                            </article><?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="sf-values"><?php foreach ($items as $item): ?>
                            <article><i class="ph <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p><?= htmlspecialchars($item['subtitle']) ?></p>
                            </article><?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php break;
        case 'categories': ?>
            <section class="sf-section sf-reference sf-category-section" id="categories" aria-labelledby="title-categories">
                <div class="sf-container"><?php $homeTitle($section, 'title-categories'); ?>
                    <div class="sf-categories">
                        <?php foreach ($collections['categories'] as $item): ?><a class="sf-category-art"
                                href="<?= htmlspecialchars(gawdee_public_url($item['url'], 'products.php')) ?>"
                                aria-label="Shop <?= htmlspecialchars($item['title']) ?>"><?php if ($item['image']):
                                      gawdee_artwork($item['image'], $item['image_crop'] ?? '', $item['title'] . ' — ' . $item['subtitle']);
                                  else: ?><span><?= htmlspecialchars($item['title']) ?>
                                        <i class="ph ph-arrow-right"></i></span><?php endif; ?></a><?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php break;
        case 'shop':
            $featured = [];
            foreach ($collections['bestsellers'] as $item)
                if (isset($activeProducts[$item['product_id']]))
                    $featured[] = ['product' => $activeProducts[$item['product_id']], 'presentation' => $item];
            if (!$featured)
                break; ?>
            <section class="sf-section sf-reference sf-bestsellers" id="shop" aria-labelledby="bestseller-title">
                <div class="sf-container">
                    <?php $homeTitle($section, 'bestseller-title', 'Bestsellers');
                    gawdee_benefit_line($collections['bestseller_benefits']); ?>
                    <div class="sf-product-grid sf-product-grid--bestsellers" data-product-grid>
                        <?php foreach ($featured as $entry)
                            gawdee_card_product($entry['product'], $entry['presentation']); ?>
                    </div>
                    <p data-product-empty class="sf-empty" hidden>No products match your search. <a href="products.php">Browse all
                            products</a></p>
                    <p class="sf-signoff"><i class="ph ph-plant"
                            aria-hidden="true"></i><span><?= htmlspecialchars(gawdee_site()['brand_tagline']) ?></span></p>
                </div>
            </section>
            <?php break;
        case 'combos': ?>
            <section class="sf-section sf-reference sf-combos" id="offers" aria-labelledby="combo-title">
                <div class="sf-container">
                    <?php $homeTitle($section, 'combo-title');
                    gawdee_benefit_line($collections['combo_benefits']); ?>
                    <div class="sf-combo-grid">
                        <?php foreach ($collections['combos'] as $combo):
                            $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $combo['product_ids'])))));
                            if (array_diff($ids, array_keys($activeProducts)) || count($ids) < 2)
                                continue;
                            $bundle = array_map(static fn($id) => $activeProducts[$id], $ids);
                            $price = array_sum(array_column($bundle, 'price'));
                            $mrp = array_sum(array_column($bundle, 'original_price'));
                            $inStock = min(array_column($bundle, 'stock')) > 0;
                            $payload = array_map(static fn($p) => ['id' => $p['id'], 'name' => $p['full_name'], 'price' => $p['price'], 'image' => $p['image']], $bundle);
                            ?>
                            <article class="sf-product sf-combo">
                                <div class="sf-product__media product-image-swap" data-product-image-swap tabindex="0"
                                    aria-label="<?= htmlspecialchars($combo['title']) ?>">
                                    <img class="product-image-swap__image product-image-swap__primary"
                                        src="<?= htmlspecialchars(gawdee_public_url($combo['image'])) ?>"
                                        alt="<?= htmlspecialchars($combo['title'] . ': ' . implode(', ', array_column($bundle, 'name'))) ?>"
                                        loading="lazy" width="500" height="500">
                                    <?php if ($combo['hover_image'] && $combo['hover_image'] !== $combo['image']): ?><img
                                            class="product-image-swap__image product-image-swap__secondary"
                                            src="<?= htmlspecialchars(gawdee_public_url($combo['hover_image'])) ?>" alt="" loading="lazy"
                                            aria-hidden="true"><?php endif; ?>
                                    <?php if ($mrp > $price): ?><span
                                            class="sf-product__badge"><?= (int) round((1 - $price / $mrp) * 100) ?>%
                                            OFF</span><?php endif; ?>
                                </div>
                                <div class="sf-product__body"><small><?= htmlspecialchars($combo['subtitle']) ?></small>
                                    <h3><?= htmlspecialchars($combo['title']) ?></h3>
                                    <p class="sf-product__meta"><?= htmlspecialchars(implode(' + ', array_column($bundle, 'weight'))) ?>
                                    </p>
                                    <p class="sf-product__price">
                                        <strong><?= money($price) ?></strong><?php if ($mrp > $price): ?><s><?= money($mrp) ?></s><?php endif; ?>
                                    </p>
                                    <div class="sf-product__actions"><button type="button" data-add-combo
                                            data-combo-name="<?= htmlspecialchars($combo['title']) ?>"
                                            data-combo-items="<?= htmlspecialchars(json_encode($payload), ENT_QUOTES) ?>" <?= $inStock ? '' : 'disabled' ?>><?= $inStock ? 'Add Combo' : 'Sold out' ?> <i class="ph ph-shopping-cart"
                                                aria-hidden="true"></i></button><button type="button" data-wishlist
                                            data-product-ids="<?= htmlspecialchars(implode(',', $ids)) ?>"
                                            aria-label="Save <?= htmlspecialchars($combo['title']) ?>" aria-pressed="false"><i
                                                class="ph ph-heart"></i></button></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php break;
        case 'assurance': ?>
            <section class="sf-section sf-reference sf-promise" id="quality-promise" aria-labelledby="quality-promise-title">
                <div class="sf-container"><?php $homeTitle($section, 'quality-promise-title'); ?>
                    <div class="sf-quality-grid"><?php foreach ($collections['quality'] as $index => $item): ?>
                            <article class="sf-quality-card <?= $item['image'] ? '' : 'sf-quality-card--document' ?>">
                                <?php if ($item['image']): ?><img class="sf-quality-card__photo"
                                        src="<?= htmlspecialchars(gawdee_public_url($item['image'])) ?>" alt="" loading="lazy" width="1122"
                                        height="1402"><?php endif; ?>
                                <div class="sf-quality-card__copy"><span
                                        class="sf-quality-card__number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><i
                                        class="ph-duotone <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                                    <p><?= htmlspecialchars($item['text']) ?></p>
                                </div><?php if (!$item['image']): ?>
                                    <div class="sf-quality-document" aria-hidden="true"><img
                                            src="<?= htmlspecialchars(gawdee_site()['brand_logo']) ?>" alt=""><strong>KNOW YOUR
                                            PRODUCT</strong><span>Ingredients <i class="ph ph-check-circle"></i></span><span>Source &
                                            preparation <i class="ph ph-check-circle"></i></span><span>Usage & storage <i
                                                class="ph ph-check-circle"></i></span><span>Clear product information</span></div>
                                <?php endif; ?><strong class="sf-quality-card__pill"><i
                                        class="ph <?= htmlspecialchars($item['icon']) ?>"
                                        aria-hidden="true"></i><span><?= htmlspecialchars($item['subtitle']) ?></span></strong>
                            </article><?php endforeach; ?>
                    </div>
                    <div class="sf-promise__bottom"><?php gawdee_benefit_line($collections['quality_benefits']); ?><a
                            class="sf-button"
                            href="<?= htmlspecialchars(gawdee_public_url($section['button_url'], 'products.php')) ?>"><?= htmlspecialchars($section['button_label'] ?: 'Explore Our Products') ?>
                            <i class="ph ph-arrow-right" aria-hidden="true"></i></a></div>
                </div>
            </section>
            <?php break;
        case 'about': ?>
            <section class="sf-section" id="about" aria-labelledby="title-about">
                <div class="sf-container sf-story"><img
                        src="<?= htmlspecialchars(gawdee_public_url($section['image'], 'assets/images/gawdee-a2-farm-hero-v1.png')) ?>"
                        alt="<?= htmlspecialchars($section['title']) ?>" loading="lazy" width="700" height="450">
                    <div><?php $homeTitle($section, 'title-about'); ?>
                        <p><?= nl2br(htmlspecialchars($section['body'])) ?></p><a class="sf-button" href="products.php">Explore the
                            collection <i class="ph ph-arrow-right"></i></a>
                    </div>
                </div>
            </section>
            <?php break;
        case 'offer':
            if (!$section['image'])
                break; ?>
            <section class="sf-section sf-offer" id="offer">
                <div class="sf-container"><a
                        href="<?= htmlspecialchars(gawdee_public_url($section['button_url'], 'products.php')) ?>"
                        aria-label="<?= htmlspecialchars($section['title']) ?>">
                        <picture><?php if ($section['mobile_image']): ?>
                                <source media="(max-width:600px)"
                                    srcset="<?= htmlspecialchars(gawdee_public_url($section['mobile_image'])) ?>"><?php endif; ?><img
                                src="<?= htmlspecialchars(gawdee_public_url($section['image'])) ?>"
                                alt="<?= htmlspecialchars($section['title'] . ' ' . $section['subtitle']) ?>" loading="lazy">
                        </picture>
                    </a></div>
            </section>
            <?php break;
        case 'reviews':
            $stories = array_slice(gawdee_testimonials(), 0, 6);
            if (!$stories)
                break; ?>
            <section class="sf-section" id="reviews" aria-labelledby="title-reviews">
                <div class="sf-container"><?php $homeTitle($section, 'title-reviews'); ?>
                    <div class="sf-reviews"><?php foreach ($stories as $item): ?>
                            <article><span class="sf-stars"
                                    aria-label="<?= (int) $item['rating'] ?> out of 5"><?= str_repeat('★', (int) $item['rating']) ?></span>
                                <blockquote>“<?= htmlspecialchars($item['quote']) ?>”</blockquote>
                                <div><?php if ($item['avatar']): ?><img
                                            src="<?= htmlspecialchars(gawdee_public_url($item['avatar'])) ?>" alt=""
                                            loading="lazy"><?php else: ?><b><?= htmlspecialchars($item['initials']) ?></b><?php endif; ?>
                                    <p><strong><?= htmlspecialchars($item['name']) ?></strong><small><?= htmlspecialchars($item['product_name']) ?></small>
                                    </p>
                                </div>
                            </article><?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php break;
        case 'video_testimonials':
            $videos = array_slice(gawdee_video_testimonials(), 0, 6);
            if (!$videos)
                break; ?>
            <section class="sf-section" id="video-testimonials" aria-labelledby="video-testimonials-title">
                <div class="sf-container"><?php $homeTitle($section, 'video-testimonials-title'); ?>
                    <div class="sf-video-grid"><?php foreach ($videos as $item):
                        $embed = $embedVideo($item['external_url']); ?>
                            <article>
                                <div class="sf-video"><?php if ($item['video_type'] === 'upload'): ?><video controls preload="none"
                                            poster="<?= htmlspecialchars(gawdee_public_url($item['poster_path'])) ?>">
                                            <source src="<?= htmlspecialchars(gawdee_public_url($item['video_path'])) ?>">
                                        </video><?php elseif ($embed): ?><iframe src="<?= htmlspecialchars($embed) ?>"
                                            title="<?= htmlspecialchars($item['name']) ?> testimonial" loading="lazy"
                                            allowfullscreen></iframe><?php else: ?><a
                                            href="<?= htmlspecialchars(gawdee_public_url($item['external_url'])) ?>" target="_blank"
                                            rel="noopener">Watch story <i class="ph ph-play"></i></a><?php endif; ?></div>
                                <blockquote><?= htmlspecialchars($item['quote']) ?></blockquote>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                            </article><?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php break;
        case 'stories':
            $posts = gawdee_db()->query("SELECT * FROM blog_posts WHERE status='published' ORDER BY COALESCE(published_at,created_at) DESC LIMIT 3")->fetchAll();
            if (!$posts)
                break; ?>
            <section class="sf-section" id="journal" aria-labelledby="title-stories">
                <div class="sf-container"><?php $homeTitle($section, 'title-stories'); ?>
                    <div class="sf-journal-grid"><?php foreach ($posts as $post): ?>
                            <article><a
                                    href="blog-post.php?slug=<?= rawurlencode($post['slug']) ?>"><?php if ($post['featured_image']): ?><img
                                            src="<?= htmlspecialchars(gawdee_public_url($post['featured_image'])) ?>"
                                            alt="<?= htmlspecialchars($post['title']) ?>"
                                            loading="lazy"><?php endif; ?><small><?= htmlspecialchars($post['category']) ?></small>
                                    <h3><?= htmlspecialchars($post['title']) ?></h3>
                                    <p><?= htmlspecialchars($post['excerpt']) ?></p><span class="sf-text-link">Read story <i
                                            class="ph ph-arrow-right"></i></span>
                                </a></article><?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php break;
        case 'reels':
            $media = gawdee_homepage_media('reels');
            if (!$media)
                break; ?>
            <section class="sf-section" id="moments" aria-labelledby="title-reels">
                <div class="sf-container"><?php $homeTitle($section, 'title-reels'); ?>
                    <div class="sf-media-grid"><?php foreach (array_slice($media, 0, 6) as $item): ?><a
                                href="<?= htmlspecialchars(gawdee_public_url($item['link_url'], 'products.php')) ?>"><img
                                    src="<?= htmlspecialchars(gawdee_public_url($item['file_path'])) ?>"
                                    alt="<?= htmlspecialchars($item['alt_text'] ?: $item['title']) ?>"
                                    loading="lazy"><span><?= htmlspecialchars($item['title']) ?></span></a><?php endforeach; ?></div>
                </div>
            </section>
            <?php break;
        case 'newsletter': ?>
            <section class="sf-section sf-newsletter">
                <div class="sf-container">
                    <div><span class="sf-eyebrow"><?= htmlspecialchars($section['eyebrow']) ?></span>
                        <h2><?= htmlspecialchars($section['title']) ?></h2>
                        <p><?= htmlspecialchars($section['subtitle']) ?></p>
                    </div>
                    <form data-newsletter-form><label class="sr-only" for="newsletter-email">Email address</label><input
                            id="newsletter-email" type="email" autocomplete="email" placeholder="Your email address"
                            required><button type="submit"><?= htmlspecialchars($section['button_label'] ?: 'Subscribe') ?> <i
                                class="ph ph-arrow-right"></i></button></form>
                </div>
            </section>
            <?php break;
    endswitch;
endforeach;
require __DIR__ . '/includes/footer.php';
