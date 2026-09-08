<?php
declare(strict_types=1);

require_once __DIR__ . '/platform.php';

function gawdee_public_url(string $value, string $fallback = ''): string
{
    $value = trim($value);
    if ($value === '') return $fallback;
    if (preg_match('/[\x00-\x20\\\\]/', $value) || str_starts_with($value, '//')) return $fallback;
    if (preg_match('~^https?://~i', $value)) return filter_var($value, FILTER_VALIDATE_URL) ? $value : $fallback;
    if (preg_match('~^mailto:[^?]+$~i', $value)) return filter_var(substr($value, 7), FILTER_VALIDATE_EMAIL) ? $value : $fallback;
    if (preg_match('~^tel:\+?[0-9-]+$~', $value)) return $value;
    if (str_contains($value, ':') || preg_match('~(^|/)\.\.(/|$)~', rawurldecode($value))) return $fallback;
    return $value;
}

function gawdee_site_fields(): array
{
    return [
        'Brand & appearance' => [
            'brand_name' => ['Store name', 'text', 'Gawdee'],
            'brand_logo' => ['Logo image path or URL', 'url', 'assets/images/logo.png'],
            'brand_color' => ['Primary green', 'color', '#086b50'],
            'brand_accent' => ['Accent colour', 'color', '#c68b37'],
            'brand_tagline' => ['Brand tagline', 'text', 'Good Food, Brighter Lives.'],
            'site_density' => ['Layout density', 'density', 'compact'],
            'site_show_whatsapp' => ['Show WhatsApp support', 'toggle', '1'],
            'site_show_chat' => ['Show chat assistant', 'toggle', '1'],
        ],
        'Header & navigation' => [
            'announcement_text' => ['Announcement', 'text', 'Pure. Natural. Trusted. • Free shipping on orders ₹{threshold}+'],
            'announcement_url' => ['Announcement destination', 'url', 'products.php'],
            'header_shop_label' => ['Shop button label', 'text', 'Shop Now'],
            'header_benefit_strip' => ['Use the reference benefit strip on the homepage', 'toggle', '1'],
            'home_hero_slideshow' => ['Use active banners as a hero slideshow', 'toggle', '0'],
        ],
        'Footer & community' => [
            'footer_description' => ['Footer description', 'textarea', "Natural nutrition. Trusted purity.\nHealthier families. A brighter tomorrow."],
            'footer_signup_title' => ['Newsletter title', 'text', 'Join Our Healthy Living Community'],
            'footer_signup_text' => ['Newsletter description', 'text', 'Get product updates, recipes and exclusive offers.'],
            'social_instagram' => ['Instagram URL', 'url', 'https://www.instagram.com/gawdee_organic/'],
            'social_facebook' => ['Facebook URL', 'url', 'https://www.facebook.com/GawdeeOrganic/'],
            'social_youtube' => ['YouTube URL', 'url', 'https://www.youtube.com/@GawdeeOrganic'],
            'app_apple' => ['App Store URL (optional)', 'url', ''],
            'app_google' => ['Google Play URL (optional)', 'url', ''],
        ],
        'Login page' => [
            'login_title' => ['Login title', 'text', 'Welcome back'],
            'login_intro' => ['Login introduction', 'text', 'Sign in to manage your orders and saved favourites.'],
            'login_story_title' => ['Story heading', 'text', 'Pure Goodness for a Healthier Tomorrow'],
            'login_story_text' => ['Story description', 'textarea', 'Natural nutrition. Trusted purity. Healthier families. A brighter tomorrow.'],
        ],
        'Product page' => [
            'pdp_goodness_title' => ['Product highlights heading','text','The Goodness Inside'],
            'pdp_goodness_text' => ['Product highlights introduction','text','More than a product. A thoughtful everyday choice.'],
            'pdp_reviews_title' => ['Reviews heading','text','Customer reviews'],
            'pdp_story_text' => ['Sourcing story text','textarea','Every pack begins with careful sourcing and a respect for nature, people and authentic food.'],
            'pdp_tomorrow_title' => ['Closing story heading','text','Building a Healthier Tomorrow'],
            'pdp_tomorrow_text' => ['Closing story text','textarea','At Gawdee, we believe in better food, happier families and a kinder world. Our journey is rooted in purity, sustainability and the well-being of future generations.'],
            'pdp_show_story' => ['Show sourcing story','toggle','1'],
            'pdp_show_highlights' => ['Show product highlights','toggle','1'],
            'pdp_show_uses' => ['Show usage ideas','toggle','1'],
            'pdp_show_benefits' => ['Show key benefits','toggle','1'],
            'pdp_show_faq' => ['Show product FAQs','toggle','1'],
            'pdp_show_comparison' => ['Show comparison when supplied','toggle','1'],
            'pdp_show_reviews' => ['Show customer reviews','toggle','1'],
            'pdp_show_related' => ['Show related products','toggle','1'],
            'pdp_show_tomorrow' => ['Show closing story','toggle','1'],
        ],
        'Help pages' => [
            'catalogue_title' => ['All-products heading', 'text', 'Pure essentials for every day'],
            'catalogue_intro' => ['All-products introduction', 'text', 'Explore ghee, honey, everyday nutrition and pantry favourites.'],
            'catalogue_image' => ['All-products hero image', 'url', 'assets/images/hero-product-collage-v2.png'],
            'page_shipping' => ['Shipping & delivery content', 'textarea', "We deliver across India to serviceable pincodes.\n\nShipping charges and the available payment methods are shown at checkout. Track your order from My Account after it is dispatched.\n\nFor delivery questions, contact our support team with your order number."],
            'page_returns' => ['Returns & refunds content', 'textarea', "If your delivery is damaged, incorrect or incomplete, contact our support team with your order number and photos of the package.\n\nOur team will review the request and explain the available return, replacement or refund options."],
            'page_contact' => ['Contact page content', 'textarea', 'Need help choosing a product or checking an order? Contact our team using the details below.'],
        ],
    ];
}

function gawdee_site(): array
{
    $defaults = [];
    foreach (gawdee_site_fields() as $fields) foreach ($fields as $key => $field) $defaults[$key] = $field[2];
    $saved = json_decode(gawdee_setting('site_design_v1', '{}'), true);
    return array_replace($defaults, is_array($saved) ? array_intersect_key($saved, $defaults) : []);
}

/** Render a measured region of an existing artwork without altering the source file. */
function gawdee_image_crop(string $value): array
{
    if (!preg_match('/^\d+(?:,\d+){5}$/', $value)) return [];
    [$x,$y,$w,$h,$sw,$sh] = array_map('intval',explode(',',$value));
    return $w > 0 && $h > 0 && $sw <= 20000 && $sh <= 20000 && $x+$w <= $sw && $y+$h <= $sh ? [$x,$y,$w,$h,$sw,$sh] : [];
}

function gawdee_artwork(string $image, string $crop = '', string $alt = '', string $class = ''): void
{
    $rect = gawdee_image_crop($crop);
    $style = '';
    if ($rect) {
        [$x,$y,$w,$h,$sw,$sh] = $rect;
        $style = sprintf('--art-ratio:%d/%d;--art-width:%.6f%%;--art-height:%.6f%%;--art-left:%.6f%%;--art-top:%.6f%%;', $w,$h,100*$sw/$w,100*$sh/$h,-100*$x/$w,-100*$y/$h);
    }
    ?><span class="sf-artwork <?= $rect ? 'sf-artwork--crop' : '' ?> <?= htmlspecialchars($class) ?>" <?= $style ? 'style="'.$style.'"' : '' ?>><img src="<?= htmlspecialchars(gawdee_public_url($image)) ?>" alt="<?= htmlspecialchars($alt) ?>" loading="lazy" decoding="async"></span><?php
}

function gawdee_reference_values(string $type): array
{
    $icons = [
        'farm'=>['ph-house-line','54,48,137,100,2051,287'], 'lab'=>['ph-flask','321,47,91,100,2051,287'],
        'heart'=>['ph-heartbeat','577,47,112,100,2051,287'], 'leaf'=>['ph-leaf','849,47,121,100,2051,287'],
        'cow'=>['ph-cow','1097,47,123,100,2051,287'], 'recycle'=>['ph-recycle','1370,43,116,106,2051,287'],
        'truck'=>['ph-truck','1617,49,126,100,2051,287'], 'shield'=>['ph-shield-check','1868,44,112,105,2051,287'],
    ];
    $rows = $type === 'trust' ? [
        ['farm','Sourced from\nTrusted Indian Farms','Pure by Origin'],['lab','Lab Tested\nfor Purity','Quality You Can Trust'],
        ['heart','Better Nutrition\nfor a Healthier You','Goodness in Every Spoon'],['leaf','No Preservatives\nNo Additives','100% Natural'],
        ['cow','Ethically Sourced\nHappy Cows','Care for Animals'],['recycle','Sustainable\nPackaging','For a Greener Tomorrow'],
        ['truck','Fast & Safe\nDelivery','Across India'],['shield','Trusted by\nThousands','Natural. Authentic. Safe.'],
    ] : [
        ['farm','Sourced from\nTrusted Indian Farms','Authentic & Ethical'],['leaf','100% Natural','No Preservatives'],
        ['lab','Lab Tested','for Purity'],['heart','Better Nutrition','for a Healthier You'],
        ['leaf','Supports\nOrganic Farming','Empowering Farmers'],['shield','Safe for\nYour Family','Quality You Can Trust'],
        ['recycle','Eco-Friendly','A Greener Tomorrow'],['truck','Fast & Reliable\nDelivery','Goodness at Your Doorstep'],
    ];
    return array_map(static function($row) use($icons) {
        return ['title'=>str_replace('\\n',"\n",$row[1]),'subtitle'=>$row[2],'icon'=>$icons[$row[0]][0], 'image'=>'assets/images/gawdee-trust-promises-reference-v1.png','image_crop'=>$icons[$row[0]][1]];
    },$rows);
}

function gawdee_heading(string $value): string
{
    return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', htmlspecialchars($value));
}

function gawdee_benefit_line(array $items, string $class = ''): void
{
    ?><ul class="sf-benefit-line <?= htmlspecialchars($class) ?>"><?php foreach($items as $item): ?><li><i class="ph <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i><span><?= htmlspecialchars($item['title']) ?></span></li><?php endforeach; ?></ul><?php
}

function gawdee_collection_defaults(): array
{
    return [
        'footer_shop' => [['title'=>'All Products','url'=>'products.php'],['title'=>'Bestsellers','url'=>'index.php#shop'],['title'=>'New Arrivals','url'=>'products.php?sort=newest'],['title'=>'Combo Deals','url'=>'index.php#offers'],['title'=>'Wishlist','url'=>'wishlist.php']],
        'footer_about' => [['title'=>'Our Story','url'=>'index.php#about'],['title'=>'Our Farms','url'=>'index.php#farms'],['title'=>'Quality Promise','url'=>'index.php#quality-promise'],['title'=>'Journal','url'=>'blog.php']],
        'footer_help' => [['title'=>'Track Order','url'=>'account.php'],['title'=>'Shipping & Delivery','url'=>'page.php?slug=shipping'],['title'=>'Returns & Refunds','url'=>'page.php?slug=returns'],['title'=>'Contact Us','url'=>'page.php?slug=contact']],
        'navigation' => [
            ['title'=>'Home','url'=>'index.php'], ['title'=>'A2 Ghee','url'=>'products.php?category=ghee'],
            ['title'=>'Natural Honey','url'=>'products.php?category=honey'], ['title'=>'Organic Foods','url'=>'products.php?category=sugar'],
            ['title'=>'Wellness','url'=>'products.php?category=wellness'], ['title'=>'Our Farms','url'=>'index.php#farms'], ['title'=>'About Us','url'=>'index.php#about'],
        ],
        'header_benefits' => [
            ['title'=>'Pure. Natural. Trusted.','icon'=>'ph-leaf'],
            ['title'=>'100% A2 Ghee','icon'=>'ph-cow'],
            ['title'=>'No Preservatives','icon'=>'ph-leaf'],
            ['title'=>'Handcrafted on Indian Farms','icon'=>'ph-hand-heart'],
            ['title'=>'Plastic Neutral','icon'=>'ph-recycle'],
            ['title'=>'Free Shipping on Orders ₹{threshold}+','icon'=>'ph-truck'],
        ],
        'trust' => gawdee_reference_values('trust'),
        'why_choose' => gawdee_reference_values('why'),
        'bestseller_benefits' => [
            ['title'=>'100% Natural','icon'=>'ph-leaf'],['title'=>'No Preservatives','icon'=>'ph-flask'],
            ['title'=>'Trusted by Families','icon'=>'ph-heart'],['title'=>'Fast Delivery','icon'=>'ph-truck'],['title'=>'Quality Assured','icon'=>'ph-shield-check'],
        ],
        'combo_benefits' => [
            ['title'=>'100% Natural','icon'=>'ph-plant'],['title'=>'Trusted Quality','icon'=>'ph-shield-check'],
            ['title'=>'Fast Delivery','icon'=>'ph-truck'],['title'=>'Better Value','icon'=>'ph-heart'],
        ],
        'quality_benefits' => [
            ['title'=>'Pure Products','icon'=>'ph-plant'],['title'=>'Better Nutrition','icon'=>'ph-heart'],['title'=>'Happier Families','icon'=>'ph-users-three'],
        ],
        'product_promises' => [
            ['title'=>'Lab Tested','subtitle'=>'For purity','icon'=>'ph-flask'],
            ['title'=>'Carefully Sourced','subtitle'=>'Selected ingredients','icon'=>'ph-plant'],
            ['title'=>'Quality First','subtitle'=>'Care in every pack','icon'=>'ph-shield-check'],
            ['title'=>'To Your Door','subtitle'=>'Delivery across India','icon'=>'ph-truck'],
        ],
        'categories' => [
            ['title'=>'A2 Gir Cow Ghee','subtitle'=>'Pure. Traditional. Nourishing.','url'=>'products.php?category=ghee','image'=>'assets/images/shop-by-category-reference-v1.png','image_crop'=>'56,140,390,198,2048,353','icon'=>'ph-cow'],
            ['title'=>'Natural Honey','subtitle'=>'Raw & Naturally Rich','url'=>'products.php?category=honey','image'=>'assets/images/shop-by-category-reference-v1.png','image_crop'=>'466,140,362,198,2048,353','icon'=>'ph-drop'],
            ['title'=>'Natural Sugars','subtitle'=>'Traditional Sweetness','url'=>'products.php?category=sugar','image'=>'assets/images/shop-by-category-reference-v1.png','image_crop'=>'846,140,367,198,2048,353','icon'=>'ph-grains'],
            ['title'=>'Grains & Millets','subtitle'=>'Everyday Nourishment','url'=>'products.php?category=grains','image'=>'assets/images/shop-by-category-reference-v1.png','image_crop'=>'1232,140,368,198,2048,353','icon'=>'ph-grains'],
            ['title'=>'Wellness Products','subtitle'=>'Clean Daily Support','url'=>'products.php?category=wellness','image'=>'assets/images/shop-by-category-reference-v1.png','image_crop'=>'1618,140,382,198,2048,353','icon'=>'ph-leaf'],
        ],
        'bestsellers' => [
            ['product_id'=>'ghee-500','badge'=>'Bestseller','features'=>'A2 Milk | Rich Nutrition | Traditional Bilona'],
            ['product_id'=>'forest-honey','badge'=>'Popular','features'=>'Raw Honey | Naturally Rich | Carefully Sourced'],
            ['product_id'=>'burra-sugar','badge'=>'Pantry Favourite','features'=>'Traditional Process | Natural Sweetness | Everyday Use'],
            ['product_id'=>'mixme-choco','badge'=>'Family Favourite','features'=>'Multigrain Blend | Cocoa Taste | Easy to Mix'],
            ['product_id'=>'moringa','badge'=>'Wellness Choice','features'=>'Leaf Powder | Plant Based | Daily Wellness'],
        ],
        'combos' => [
            ['title'=>'Ghee + Honey Combo','subtitle'=>'Pure Goodness','product_ids'=>'ghee-500,forest-honey','image'=>'assets/images/combos/ghee-honey-combo-v1.png','hover_image'=>''],
            ['title'=>'Breakfast Combo','subtitle'=>'Breakfast Essentials','product_ids'=>'burra-sugar,mixme-choco','image'=>'assets/images/combos/breakfast-combo-v1.png','hover_image'=>''],
            ['title'=>'Immunity Combo','subtitle'=>'Daily Wellness','product_ids'=>'moringa,mixme-elaichi','image'=>'assets/images/combos/immunity-combo-v1.png','hover_image'=>''],
            ['title'=>'Traditional Combo','subtitle'=>'Traditional Goodness','product_ids'=>'ghee-500,burra-sugar','image'=>'assets/images/combos/traditional-combo-v1.png','hover_image'=>''],
            ['title'=>'MixMe Combo','subtitle'=>'Tasty Nutrition','product_ids'=>'mixme-choco,mixme-elaichi','image'=>'assets/images/combos/mixme-combo-v1.png','hover_image'=>''],
            ['title'=>'Health Combo','subtitle'=>'Complete Pantry','product_ids'=>'moringa,mixme-choco,ghee-500','image'=>'assets/images/combos/health-combo-v1.png','hover_image'=>''],
        ],
        'quality' => [
            ['title'=>'Expert Team','subtitle'=>'Experienced Nutrition & Food Experts','text'=>'Our in-house experts ensure every product meets the highest safety and quality standards.','icon'=>'ph-plant','image'=>'assets/images/quality-promise-expert-team-v1.png'],
            ['title'=>'40+ Quality Checks','subtitle'=>'Advanced In-House Lab Testing','text'=>'Every batch is tested for purity, texture, moisture, nutrition and more. Nothing gets missed.','icon'=>'ph-microscope','image'=>'assets/images/quality-promise-lab-testing-v1.png'],
            ['title'=>'Pure & Safe Ingredients','subtitle'=>'Naturally Sourced Premium Ingredients','text'=>'We carefully source the best natural ingredients, ensuring clean, healthy and nutritious products.','icon'=>'ph-shield-check','image'=>'assets/images/quality-promise-moringa-v1.png'],
            ['title'=>'Complete Transparency','subtitle'=>'Clear Information For Your Family','text'=>'Know your ingredients, their source and how to use them. Because real trust is built on clarity.','icon'=>'ph-file-text','image'=>''],
        ],
    ];
}

function gawdee_collections(): array
{
    $defaults = gawdee_collection_defaults();
    $saved = json_decode(gawdee_setting('storefront_collections_v1', '{}'), true);
    $merged = array_replace($defaults, is_array($saved) ? array_intersect_key($saved, $defaults) : []);
    foreach($merged as $group=>&$rows) {
        $fields = array_fill_keys(array_keys($defaults[$group][0]),'');
        $rows = array_map(static fn($row)=>array_replace($fields,array_intersect_key($row,$fields)),is_array($rows)?$rows:[]);
    }
    unset($rows);
    return $merged;
}

function gawdee_save_site_design(array $post): void
{
    $values = gawdee_site();
    foreach (gawdee_site_fields() as $fields) foreach ($fields as $key => [$label, $type]) {
        $value = trim((string) ($post[$key] ?? ''));
        if ($type === 'toggle') $value = isset($post[$key]) ? '1' : '0';
        if ($type === 'url' && $value !== '' && gawdee_public_url($value) === '') throw new InvalidArgumentException($label . ' must be a valid link or image path.');
        if ($type === 'color' && !preg_match('/^#[0-9a-f]{6}$/i', $value)) throw new InvalidArgumentException('Choose a valid colour.');
        if ($type === 'density' && !in_array($value, ['compact','comfortable'], true)) $value = 'compact';
        if (mb_strlen($value) > ($type === 'textarea' ? 20000 : 1000)) throw new InvalidArgumentException($label . ' is too long.');
        $values[$key] = $value;
    }
    if ($values['brand_name'] === '' || $values['brand_logo'] === '') throw new InvalidArgumentException('Store name and logo are required.');
    gawdee_set_setting('site_design_v1', json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
}

function gawdee_save_collections(array $post): void
{
    $definitions = gawdee_collection_defaults();
    $saved = gawdee_collections();
    $productIds = array_column(gawdee_products(true), 'id');
    foreach ($definitions as $group => $rows) {
        if (!isset($post[$group]) || !is_array($post[$group])) continue;
        $clean = [];
        foreach (array_slice($post[$group], 0, 30) as $row) {
            if (!is_array($row) || !empty($row['remove'])) continue;
            $fields = array_keys($rows[0]);
            $item = [];
            foreach ($fields as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if (mb_strlen($value) > 4000) throw new InvalidArgumentException('Keep each content field below 4,000 characters.');
                if (in_array($field, ['url','image','hover_image'], true) && $value !== '' && gawdee_public_url($value) === '') throw new InvalidArgumentException('Enter a valid content link or image path.');
                if ($field === 'icon' && !preg_match('/^ph-[a-z0-9-]+$/', $value)) $value = 'ph-leaf';
                if ($field === 'image_crop' && $value !== '' && !gawdee_image_crop($value)) throw new InvalidArgumentException('Image crop must be x,y,width,height,source width,source height within the source image. Leave blank for a complete uploaded card.');
                $item[$field] = $value;
            }
            if (implode('', $item) === '' || (($item['title'] ?? $item['product_id'] ?? '') === '')) continue;
            if ($group === 'bestsellers' && !in_array($item['product_id'], $productIds, true)) throw new InvalidArgumentException('Choose an existing bestseller product.');
            if ($group === 'combos') {
                $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $item['product_ids'])))));
                if (count($ids) < 2 || count($ids) > 6 || array_diff($ids, $productIds)) throw new InvalidArgumentException('A combo needs 2–6 valid product IDs.');
                $item['product_ids'] = implode(',', $ids);
            }
            $clean[] = $item;
        }
        $saved[$group] = $clean;
    }
    gawdee_set_setting('storefront_collections_v1', json_encode($saved, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
}

function gawdee_card_product(array $product, array $presentation = []): void
{
    $primary = (string) ($product['card_image'] ?? $product['image']);
    $secondary = product_hover_image($product, $primary);
    $reviews = gawdee_product_reviews((string) $product['id']);
    $count = count($reviews) + (int) $product['review_count'];
    $rating = $count ? (array_sum(array_column($reviews, 'rating')) + $product['rating'] * $product['review_count']) / $count : 0;
    ?>
    <article class="sf-product" data-category="<?= htmlspecialchars($product['category_key']) ?>" data-search-name="<?= htmlspecialchars(mb_strtolower($product['full_name'].' '.$product['category'].' '.$product['weight'])) ?>">
        <a class="sf-product__media product-image-swap" data-product-image-swap href="product.php?slug=<?= rawurlencode($product['slug']) ?>" aria-label="View <?= htmlspecialchars($product['full_name']) ?>">
            <img class="product-image-swap__image product-image-swap__primary" src="<?= htmlspecialchars($primary) ?>" alt="<?= htmlspecialchars($product['full_name']) ?>" loading="lazy" decoding="async" width="500" height="500">
            <?php if ($secondary !== $primary): ?><img class="product-image-swap__image product-image-swap__secondary" src="<?= htmlspecialchars($secondary) ?>" alt="" loading="lazy" decoding="async" aria-hidden="true"><?php endif; ?>
            <?php if ($presentation['badge'] ?? $product['tag']): ?><span class="sf-product__badge"><?= htmlspecialchars($presentation['badge'] ?? $product['tag']) ?></span><?php endif; ?>
        </a>
        <div class="sf-product__body">
            <?php if (!empty($presentation['features'])): ?><ul class="sf-product__features"><?php foreach(array_slice(array_filter(array_map('trim',explode('|',$presentation['features']))),0,3) as $n=>$feature): ?><li><i class="ph <?= ['ph-leaf','ph-shield-check','ph-plant'][$n] ?>" aria-hidden="true"></i><span><?= htmlspecialchars($feature) ?></span></li><?php endforeach; ?></ul><?php else: ?><small><?= htmlspecialchars($product['category']) ?></small><?php endif; ?>
            <h3><a href="product.php?slug=<?= rawurlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
            <p class="sf-product__meta"><?= htmlspecialchars($product['weight']) ?><?php if ($count): ?><span>★ <?= number_format($rating, 1) ?> (<?= $count ?>)</span><?php endif; ?></p>
            <p class="sf-product__price"><strong><?= money($product['price']) ?></strong><?php if ($product['original_price'] > $product['price']): ?><s><?= money($product['original_price']) ?></s><?php endif; ?></p>
            <div class="sf-product__actions"><button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>" data-name="<?= htmlspecialchars($product['full_name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= htmlspecialchars($product['image']) ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>><?= $product['stock'] > 0 ? 'Add to Cart' : 'Sold out' ?> <i class="ph ph-shopping-cart" aria-hidden="true"></i></button><button type="button" data-wishlist data-product-id="<?= htmlspecialchars($product['id']) ?>" aria-label="Save <?= htmlspecialchars($product['name']) ?>" aria-pressed="false"><i class="ph ph-heart" aria-hidden="true"></i></button></div>
        </div>
    </article>
    <?php
}

function gawdee_combo_data(array $combo, array $catalogue): ?array
{
    $ids = array_values(array_unique(array_filter(array_map('trim',explode(',',$combo['product_ids'])))));
    if(count($ids)<2 || array_diff($ids,array_keys($catalogue))) return null;
    $bundle = array_map(static fn($id)=>$catalogue[$id],$ids);
    return array_merge($combo, ['ids'=>$ids,'bundle'=>$bundle,'price'=>array_sum(array_column($bundle,'price')),'mrp'=>array_sum(array_column($bundle,'original_price')),'stock'=>min(array_column($bundle,'stock'))]);
}

function gawdee_card_combo(array $combo): void
{
    $payload = array_map(static fn($p)=>['id'=>$p['id'],'name'=>$p['full_name'],'price'=>$p['price'],'image'=>$p['image']],$combo['bundle']);
    ?>
    <article class="sf-product sf-combo">
        <div class="sf-product__media product-image-swap" data-product-image-swap tabindex="0" aria-label="<?= htmlspecialchars($combo['title']) ?>">
            <img class="product-image-swap__image product-image-swap__primary" src="<?= htmlspecialchars(gawdee_public_url($combo['image'])) ?>" alt="<?= htmlspecialchars($combo['title'].': '.implode(', ',array_column($combo['bundle'],'name'))) ?>" width="500" height="500" loading="lazy">
            <?php if($combo['hover_image'] && $combo['hover_image']!==$combo['image']): ?><img class="product-image-swap__image product-image-swap__secondary" src="<?= htmlspecialchars(gawdee_public_url($combo['hover_image'])) ?>" alt="" aria-hidden="true" loading="lazy"><?php endif; ?>
            <?php if($combo['mrp']>$combo['price']): ?><span class="sf-product__badge"><?= (int)round((1-$combo['price']/$combo['mrp'])*100) ?>% OFF</span><?php endif; ?>
        </div>
        <div class="sf-product__body"><small><?= htmlspecialchars($combo['subtitle']) ?></small><h3><?= htmlspecialchars($combo['title']) ?></h3><p class="sf-product__meta"><?= htmlspecialchars(implode(' + ',array_column($combo['bundle'],'weight'))) ?></p><p class="sf-product__price"><strong><?= money($combo['price']) ?></strong><?php if($combo['mrp']>$combo['price']): ?><s><?= money($combo['mrp']) ?></s><?php endif; ?></p><div class="sf-product__actions"><button type="button" data-add-combo data-combo-name="<?= htmlspecialchars($combo['title']) ?>" data-combo-items="<?= htmlspecialchars(json_encode($payload),ENT_QUOTES) ?>" <?= $combo['stock']>0?'':'disabled' ?>><?= $combo['stock']>0?'Add Combo':'Sold out' ?> <i class="ph ph-shopping-cart" aria-hidden="true"></i></button><button type="button" data-wishlist data-product-ids="<?= htmlspecialchars(implode(',',$combo['ids'])) ?>" aria-label="Save <?= htmlspecialchars($combo['title']) ?>" aria-pressed="false"><i class="ph ph-heart" aria-hidden="true"></i></button></div></div>
    </article>
    <?php
}
