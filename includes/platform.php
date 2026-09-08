<?php

declare(strict_types=1);

const GAWDEE_ROOT = __DIR__ . '/..';
const GAWDEE_STORAGE = GAWDEE_ROOT . '/storage';
if (!defined('GAWDEE_DB')) define('GAWDEE_DB', GAWDEE_STORAGE . '/gawdee.sqlite');

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gawdee_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function gawdee_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(GAWDEE_STORAGE)) {
        mkdir(GAWDEE_STORAGE, 0750, true);
    }

    $pdo = new PDO('sqlite:' . GAWDEE_DB, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    gawdee_migrate($pdo);

    return $pdo;
}

function gawdee_migrate(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    phone TEXT NOT NULL DEFAULT '',
    address1 TEXT NOT NULL DEFAULT '',
    address2 TEXT NOT NULL DEFAULT '',
    city TEXT NOT NULL DEFAULT '',
    state TEXT NOT NULL DEFAULT '',
    pincode TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL DEFAULT '',
    is_secret INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS saved_products (
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_key TEXT NOT NULL,
    product_ids TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, item_key)
);

CREATE TABLE IF NOT EXISTS products (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    full_name TEXT NOT NULL,
    category TEXT NOT NULL,
    category_key TEXT NOT NULL,
    tag TEXT NOT NULL DEFAULT '',
    price INTEGER NOT NULL,
    original_price INTEGER NOT NULL,
    weight TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    accent TEXT NOT NULL DEFAULT '#0a7540',
    stock INTEGER NOT NULL DEFAULT 100,
    stock_status TEXT NOT NULL DEFAULT 'in_stock',
    sku TEXT NOT NULL DEFAULT '',
    source_id TEXT NOT NULL DEFAULT '',
    source_url TEXT NOT NULL DEFAULT '',
    rating REAL NOT NULL DEFAULT 0,
    review_count INTEGER NOT NULL DEFAULT 0,
    gallery_json TEXT NOT NULL DEFAULT '[]',
    details_json TEXT NOT NULL DEFAULT '{}',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    desktop_image TEXT NOT NULL,
    mobile_image TEXT NOT NULL DEFAULT '',
    link_url TEXT NOT NULL DEFAULT '#shop',
    alt_text TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_sections (
    section_key TEXT PRIMARY KEY,
    eyebrow TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    body TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    mobile_image TEXT NOT NULL DEFAULT '',
    video_url TEXT NOT NULL DEFAULT '',
    button_label TEXT NOT NULL DEFAULT '',
    button_url TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    initials TEXT NOT NULL DEFAULT '',
    avatar TEXT NOT NULL DEFAULT '',
    product_name TEXT NOT NULL DEFAULT '',
    product_slug TEXT NOT NULL DEFAULT '',
    quote TEXT NOT NULL,
    rating INTEGER NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    theme TEXT NOT NULL DEFAULT 'ghee',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS homepage_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    section_key TEXT NOT NULL DEFAULT 'reels',
    media_type TEXT NOT NULL DEFAULT 'image',
    title TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    file_path TEXT NOT NULL DEFAULT '',
    poster_path TEXT NOT NULL DEFAULT '',
    external_url TEXT NOT NULL DEFAULT '',
    link_url TEXT NOT NULL DEFAULT '',
    alt_text TEXT NOT NULL DEFAULT '',
    product_slug TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    role_location TEXT NOT NULL DEFAULT '',
    quote TEXT NOT NULL DEFAULT '',
    rating INTEGER NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    video_type TEXT NOT NULL DEFAULT 'upload',
    video_path TEXT NOT NULL DEFAULT '',
    poster_path TEXT NOT NULL DEFAULT '',
    external_url TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_section_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    section_key TEXT NOT NULL,
    icon TEXT NOT NULL DEFAULT 'ph-leaf',
    title TEXT NOT NULL,
    subtitle TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    link_url TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    excerpt TEXT NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    source TEXT NOT NULL DEFAULT 'manual',
    ai_provider TEXT NOT NULL DEFAULT '',
    meta_description TEXT NOT NULL DEFAULT '',
    featured_image TEXT NOT NULL DEFAULT '',
    category TEXT NOT NULL DEFAULT 'Wellness',
    author TEXT NOT NULL DEFAULT 'Gawdee editorial',
    is_featured INTEGER NOT NULL DEFAULT 0,
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    order_number TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'pending',
    payment_method TEXT NOT NULL DEFAULT 'razorpay',
    payment_status TEXT NOT NULL DEFAULT 'pending',
    shipment_status TEXT NOT NULL DEFAULT 'not_created',
    currency TEXT NOT NULL DEFAULT 'INR',
    subtotal INTEGER NOT NULL,
    shipping INTEGER NOT NULL DEFAULT 0,
    discount INTEGER NOT NULL DEFAULT 0,
    total INTEGER NOT NULL,
    coupon_code TEXT NOT NULL DEFAULT '',
    checkout_token TEXT NOT NULL DEFAULT '',
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    address1 TEXT NOT NULL,
    address2 TEXT NOT NULL DEFAULT '',
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    pincode TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    razorpay_order_id TEXT NOT NULL DEFAULT '',
    razorpay_payment_id TEXT NOT NULL DEFAULT '',
    razorpay_signature TEXT NOT NULL DEFAULT '',
    dtdc_reference TEXT NOT NULL DEFAULT '',
    dtdc_tracking_url TEXT NOT NULL DEFAULT '',
    fulfillment_mode TEXT NOT NULL DEFAULT 'manual',
    courier_name TEXT NOT NULL DEFAULT '',
    tracking_number TEXT NOT NULL DEFAULT '',
    tracking_url TEXT NOT NULL DEFAULT '',
    inventory_status TEXT NOT NULL DEFAULT 'not_deducted',
    source TEXT NOT NULL DEFAULT 'storefront',
    admin_note TEXT NOT NULL DEFAULT '',
    payment_error TEXT NOT NULL DEFAULT '',
    paid_at TEXT,
    fulfilled_at TEXT,
    cancelled_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id TEXT NOT NULL,
    product_name TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price INTEGER NOT NULL,
    image TEXT NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id TEXT NOT NULL,
    order_id INTEGER,
    adjustment INTEGER NOT NULL,
    balance_after INTEGER NOT NULL,
    reason TEXT NOT NULL,
    created_by INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS integration_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    integration TEXT NOT NULL,
    action TEXT NOT NULL,
    status TEXT NOT NULL,
    reference TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id TEXT NOT NULL,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'approved',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_status_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_otps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    phone TEXT NOT NULL,
    purpose TEXT NOT NULL DEFAULT 'login',
    code_hash TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    requested_ip_hash TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    consumed_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notification_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    user_id INTEGER,
    channel TEXT NOT NULL DEFAULT 'whatsapp',
    notification_type TEXT NOT NULL,
    recipient TEXT NOT NULL,
    template_name TEXT NOT NULL,
    language TEXT NOT NULL DEFAULT 'en_US',
    variables_json TEXT NOT NULL DEFAULT '[]',
    dedupe_key TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'queued',
    attempts INTEGER NOT NULL DEFAULT 0,
    provider_message_id TEXT NOT NULL DEFAULT '',
    error_message TEXT NOT NULL DEFAULT '',
    scheduled_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS webhook_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider TEXT NOT NULL,
    event_key TEXT NOT NULL,
    event_type TEXT NOT NULL DEFAULT '',
    payload_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'received',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TEXT,
    UNIQUE(provider, event_key)
);
SQL);

    foreach ([
        'phone' => "TEXT NOT NULL DEFAULT ''",
        'address1' => "TEXT NOT NULL DEFAULT ''",
        'address2' => "TEXT NOT NULL DEFAULT ''",
        'city' => "TEXT NOT NULL DEFAULT ''",
        'state' => "TEXT NOT NULL DEFAULT ''",
        'pincode' => "TEXT NOT NULL DEFAULT ''",
        'whatsapp_marketing_opt_in' => 'INTEGER NOT NULL DEFAULT 0',
        'whatsapp_marketing_opt_in_at' => 'TEXT',
        'whatsapp_opt_out_at' => 'TEXT',
        'updated_at' => "TEXT NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'users', $column, $definition);
    }
    foreach ([
        'stock_status' => "TEXT NOT NULL DEFAULT 'in_stock'",
        'sku' => "TEXT NOT NULL DEFAULT ''",
        'source_id' => "TEXT NOT NULL DEFAULT ''",
        'source_url' => "TEXT NOT NULL DEFAULT ''",
        'rating' => 'REAL NOT NULL DEFAULT 0',
        'review_count' => 'INTEGER NOT NULL DEFAULT 0',
        'gallery_json' => "TEXT NOT NULL DEFAULT '[]'",
        'details_json' => "TEXT NOT NULL DEFAULT '{}'",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'products', $column, $definition);
    }
    foreach ([
        'image' => "TEXT NOT NULL DEFAULT ''",
        'mobile_image' => "TEXT NOT NULL DEFAULT ''",
        'video_url' => "TEXT NOT NULL DEFAULT ''",
        'button_label' => "TEXT NOT NULL DEFAULT ''",
        'button_url' => "TEXT NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'cms_sections', $column, $definition);
    }
    foreach ([
        'featured_image' => "TEXT NOT NULL DEFAULT ''",
        'category' => "TEXT NOT NULL DEFAULT 'Wellness'",
        'author' => "TEXT NOT NULL DEFAULT 'Gawdee editorial'",
        'is_featured' => 'INTEGER NOT NULL DEFAULT 0',
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'blog_posts', $column, $definition);
    }
    gawdee_ensure_column($db, 'product_reviews', 'updated_at', "TEXT NOT NULL DEFAULT ''");
    gawdee_ensure_column($db, 'orders', 'user_id', 'INTEGER');
    foreach ([
        'discount' => 'INTEGER NOT NULL DEFAULT 0',
        'coupon_code' => "TEXT NOT NULL DEFAULT ''",
        'checkout_token' => "TEXT NOT NULL DEFAULT ''",
        'fulfillment_mode' => "TEXT NOT NULL DEFAULT 'manual'",
        'courier_name' => "TEXT NOT NULL DEFAULT ''",
        'tracking_number' => "TEXT NOT NULL DEFAULT ''",
        'tracking_url' => "TEXT NOT NULL DEFAULT ''",
        'inventory_status' => "TEXT NOT NULL DEFAULT 'not_deducted'",
        'source' => "TEXT NOT NULL DEFAULT 'storefront'",
        'admin_note' => "TEXT NOT NULL DEFAULT ''",
        'payment_error' => "TEXT NOT NULL DEFAULT ''",
        'paid_at' => 'TEXT',
        'fulfilled_at' => 'TEXT',
        'cancelled_at' => 'TEXT',
        'delhivery_waybill' => "TEXT NOT NULL DEFAULT ''",
        'delhivery_tracking_url' => "TEXT NOT NULL DEFAULT ''",
        'delhivery_label_url' => "TEXT NOT NULL DEFAULT ''",
        'delhivery_last_status' => "TEXT NOT NULL DEFAULT ''",
        'delhivery_last_sync_at' => 'TEXT',
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'orders', $column, $definition);
    }
    $db->exec('CREATE INDEX IF NOT EXISTS idx_products_category_key ON products(category_key)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_testimonials_active ON testimonials(is_active, sort_order, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_homepage_media_section ON homepage_media(section_key, is_active, sort_order, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_video_testimonials_active ON video_testimonials(is_active, sort_order, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_section_items_section ON cms_section_items(section_key, is_active, sort_order, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_product_reviews_status ON product_reviews(status, product_id, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_blog_posts_status ON blog_posts(status, published_at, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id)');
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_orders_checkout_token ON orders(checkout_token) WHERE checkout_token != ''");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_orders_workflow ON orders(status, payment_status, shipment_status, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_order_status_events_order_id ON order_status_events(order_id, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_inventory_events_product_id ON inventory_events(product_id, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_inventory_events_order_id ON inventory_events(order_id, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_customer_otps_lookup ON customer_otps(phone, purpose, status, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_customer_otps_rate ON customer_otps(requested_ip_hash, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_notification_queue_delivery ON notification_queue(status, scheduled_at, id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_notification_queue_provider_id ON notification_queue(provider_message_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_webhook_events_provider ON webhook_events(provider, created_at)');
    $db->exec('PRAGMA optimize');

    gawdee_seed_defaults($db);
}

function gawdee_ensure_column(PDO $db, string $table, string $column, string $definition): void
{
    $columns = $db->query('PRAGMA table_info(' . $table . ')')->fetchAll();
    foreach ($columns as $existing) {
        if (($existing['name'] ?? null) === $column) {
            return;
        }
    }
    $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function gawdee_seed_defaults(PDO $db): void
{
    $defaults = [
        'store_name' => 'Gawdee',
        'store_email' => 'info@gawdee.com',
        'store_phone' => '+91 70552 07030',
        'currency' => 'INR',
        'free_shipping_threshold' => '999',
        'shipping_fee' => '99',
        'cod_enabled' => '1',
        'dtdc_enabled' => '0',
        'delhivery_enabled' => '0',
        'delhivery_environment' => 'staging',
        'delhivery_pickup_location' => '',
        'delhivery_client_name' => '',
        'delhivery_origin_name' => 'Gawdee Warehouse',
        'delhivery_origin_phone' => '',
        'delhivery_origin_address' => '',
        'delhivery_origin_city' => '',
        'delhivery_origin_state' => '',
        'delhivery_origin_pincode' => '',
        'delhivery_default_weight_grams' => '500',
        'delhivery_default_length_cm' => '20',
        'delhivery_default_width_cm' => '15',
        'delhivery_default_height_cm' => '10',
        'whatsapp_cloud_enabled' => '0',
        'whatsapp_graph_version' => 'v23.0',
        'whatsapp_phone_number_id' => '',
        'whatsapp_business_account_id' => '',
        'whatsapp_language' => 'en_US',
        'whatsapp_otp_enabled' => '0',
        'whatsapp_order_notifications' => '1',
        'whatsapp_marketing_enabled' => '0',
        'whatsapp_template_otp' => 'gawdee_login_otp',
        'whatsapp_template_order_confirmed' => 'gawdee_order_confirmed',
        'whatsapp_template_payment_confirmed' => 'gawdee_payment_confirmed',
        'whatsapp_template_order_packed' => 'gawdee_order_packed',
        'whatsapp_template_order_shipped' => 'gawdee_order_shipped',
        'whatsapp_template_order_delivered' => 'gawdee_order_delivered',
        'whatsapp_template_order_cancelled' => 'gawdee_order_cancelled',
        'whatsapp_template_marketing' => 'gawdee_marketing_update',
        'offer_code' => 'FREEDOM10',
        'offer_percent' => '10',
        'offer_popup_enabled' => '1',
        'offer_popup_image' => 'assets/images/independence-offer-popup-v1.webp',
        'offer_popup_delay_ms' => '850',
        'ai_provider' => 'groq',
        'groq_model' => 'llama-3.3-70b-versatile',
        'openai_model' => 'gpt-5.6-luna',
        'ai_chat_enabled' => '1',
        'ai_auto_blog_enabled' => '0',
        'ai_blog_frequency_days' => '7',
        'ai_blog_topics' => 'traditional Indian foods, ingredient transparency, family wellness, mindful nutrition',
        'ai_last_blog_at' => '',
        'razorpay_key_id' => '',
        'dtdc_booking_endpoint' => '',
        'dtdc_tracking_endpoint' => '',
        'dtdc_customer_code' => '',
        'dtdc_service_type' => 'EXPRESS',
        'dtdc_pickup_pincode' => '',
        'site_body_font' => 'system',
        'site_heading_font' => 'system',
        'site_base_font_size' => '16',
    ];

    $insert = $db->prepare('INSERT OR IGNORE INTO settings (setting_key, setting_value, is_secret) VALUES (?, ?, 0)');
    foreach ($defaults as $key => $value) {
        $insert->execute([$key, $value]);
    }

    $sections = [
        ['hero', '100% pure • natural • tested', 'Pure by Nature. Trusted for Generations.', 'Made from the milk of free-grazed Gir cows. Our A2 Ghee is bilona-churned in small batches to bring you pure nutrition that your family deserves.', '', 1, 10],
        ['benefits', 'Everyday assurance', 'Pure nutrition, made simply', 'Five reasons families choose Gawdee for their daily pantry.', '', 1, 15],
        ['shop', 'Everyday favourites', 'Bestsellers', 'Handpicked products for everyday family routines.', '', 1, 20],
        ['categories', 'Browse the pantry', 'Shop by category', 'Find the right products for your daily rituals.', '', 1, 30],
        ['process', 'From farm to family', 'From Our Farms to Your Family', 'A slow, transparent process from free-grazed Gir cows to every jar.', '', 1, 35],
        ['offer', 'Independence Day offer', 'Flat 10% OFF', 'On all products. Use code FREEDOM10 at checkout.', 'Celebrate with better everyday wellness.', 1, 40],
        ['combos', 'Thoughtful bundles', 'Healthy combos. Greater savings.', 'Pairs designed to make everyday wellness simpler.', '', 1, 50],
        ['assurance', 'Our promise', 'Goodness without shortcuts', 'Natural ingredients, careful testing and traditional preparation.', '', 1, 55],
        ['about', 'Rooted in purity', 'Inspired by nature', 'A wholesome journey from earth to plate.', 'We bring pure A2 Gir Cow Ghee, natural honey, grain foods and wellness products made with care, authenticity and village-inspired goodness.', 1, 60],
        ['why', 'The Gawdee difference', 'Why choose Gawdee', 'Purity, tradition and nutrition for a healthier lifestyle.', '', 1, 70],
        ['reviews', 'Customer stories', 'Loved by families who choose purity daily', 'Real words from customers who value authentic taste and thoughtful quality.', '', 1, 80],
        ['video_testimonials', 'Watch their stories', 'Real families. Real Gawdee experiences.', 'Hear directly from customers who have made Gawdee part of their everyday routine.', '', 1, 85],
        ['stories', 'Gawdee journal', 'Stories for a more thoughtful table', 'Ideas, traditions and ingredient knowledge for everyday wellness.', '', 1, 90],
        ['reels', 'Made with care', 'From nature to your plate', 'A closer look at the products and people behind Gawdee.', '', 1, 100],
        ['newsletter', 'Stay close to goodness', 'Be the first to know!', 'Subscribe for special offers, health tips and updates.', '', 1, 110],
    ];
    $insertSection = $db->prepare('INSERT OR IGNORE INTO cms_sections (section_key, eyebrow, title, subtitle, body, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($sections as $section) {
        $insertSection->execute($section);
    }
    $db->exec("UPDATE cms_sections SET eyebrow='100% pure • natural • tested', title='Pure by Nature. Trusted for Generations.', subtitle='Made from the milk of free-grazed Gir cows. Our A2 Ghee is bilona-churned in small batches to bring you pure nutrition that your family deserves.', image='assets/images/gawdee-reference-poster-hero-v3.png', button_label='Shop A2 Ghee', button_url='products.php?category=ghee' WHERE section_key='hero' AND title='Pure food. Beautifully made.'");
    $db->exec("UPDATE cms_sections SET image='assets/images/gawdee-reference-poster-hero-v3.png', button_label='Shop A2 Ghee', button_url='products.php?category=ghee' WHERE section_key='hero' AND image IN ('', 'assets/images/gawdee-a2-farm-hero-v1.png', 'assets/images/gawdee-reference-poster-hero-v2.png')");
    $db->exec("UPDATE cms_sections SET image='assets/images/independence-day-offer-banner-v1.png', mobile_image='assets/images/independence-day-offer-banner-mobile-v1.png', button_label='Shop offer', button_url='#shop' WHERE section_key='offer' AND image=''");
    $db->exec("UPDATE cms_sections SET image='assets/images/blogs/blog-tree-laptop-reference-v1.png', button_label='View more', button_url='blog.php' WHERE section_key='stories' AND image=''");
    $db->exec("UPDATE cms_sections SET button_label='View all products', button_url='products.php' WHERE section_key='shop' AND button_label=''");
    $db->exec("UPDATE cms_sections SET button_label='Subscribe', button_url='#' WHERE section_key='newsletter' AND button_label=''");

    if ((int) $db->query('SELECT COUNT(*) FROM banners')->fetchColumn() === 0) {
        $insertBanner = $db->prepare('INSERT INTO banners (title, desktop_image, mobile_image, link_url, alt_text, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $insertBanner->execute(['Independence Day wellness offer', 'assets/images/hero-slide-independence-v5.webp', 'assets/images/hero-slide-independence-mobile-v5.webp', '#shop', 'Happy Independence Day. Flat 10% off with code FREEDOM10. Featuring exact Gawdee A2 Gir Cow Ghee, Burra Sugar and MixMe Choco packs.', 10]);
        $insertBanner->execute(['Traditional A2 Ghee', 'assets/images/hero-slide-ghee-v5.webp', 'assets/images/hero-slide-ghee-mobile-v5.webp', 'product.php?slug=gawdee-gir-cow-a2-ghee-500-ml', 'Freedom to choose pure tradition with Gawdee Bilona-crafted A2 Gir Cow Ghee.', 20]);
        $insertBanner->execute(['MixMe daily nutrition', 'assets/images/hero-slide-mixme-v5.webp', 'assets/images/hero-slide-mixme-mobile-v5.webp', 'product.php?slug=gawdee-mixme-choco-500-g', 'Celebrate everyday wellness with Gawdee MixMe Choco.', 30]);
    }

    if ((int) $db->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() === 0) {
        $insertTestimonial = $db->prepare('INSERT INTO testimonials (name, initials, avatar, product_name, product_slug, quote, rating, theme, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertTestimonial->execute(['Neha Shah', 'NS', 'assets/images/testimonials/neha-shah.png', 'Raw Forest Honey', 'gawdee-raw-wild-forest-honey-650-g', 'What I love most is that the honey tastes naturally rich without feeling overly processed or artificially sweet.', 5, 'honey', 10]);
        $insertTestimonial->execute(['Pooja Desai', 'PD', 'assets/images/testimonials/pooja-desai.png', 'A2 Gir Cow Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 'The aroma feels beautifully traditional, and it has become a trusted part of our family meals.', 5, 'ghee', 20]);
        $insertTestimonial->execute(['Ritu Sharma', 'RS', '', 'MixMe Choco', 'gawdee-mixme-choco-500-g', 'A simple way to add better everyday nutrition. My children genuinely enjoy the flavour.', 5, 'mixme', 30]);
        $insertTestimonial->execute(['Ananya Rao', 'AR', '', 'Moringa Powder', 'gawdee-moringa-powder-300-g', 'Clean, convenient and easy to add to my routine. The ingredient story gives me confidence.', 5, 'moringa', 40]);
    }

    if ((int) $db->query('SELECT COUNT(*) FROM homepage_media')->fetchColumn() === 0) {
        $insertMedia = $db->prepare('INSERT INTO homepage_media (section_key, media_type, title, subtitle, file_path, link_url, alt_text, product_slug, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertMedia->execute(['reels', 'image', 'Pure A2 Ghee', 'Bilona-crafted everyday goodness', 'assets/images/products/ghee-500.webp', 'product.php?slug=gawdee-gir-cow-a2-ghee-500-ml', 'Gawdee A2 Gir Cow Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 10]);
        $insertMedia->execute(['reels', 'image', 'Raw Forest Honey', 'Naturally rich and thoughtfully sourced', 'assets/images/products/forest-honey.webp', 'product.php?slug=gawdee-raw-wild-forest-honey-650-g', 'Gawdee Raw Forest Honey', 'gawdee-raw-wild-forest-honey-650-g', 20]);
        $insertMedia->execute(['reels', 'image', 'MixMe Choco', 'Family nutrition made delicious', 'assets/images/products/mixme-choco.webp', 'product.php?slug=gawdee-mixme-choco-500-g', 'Gawdee MixMe Choco', 'gawdee-mixme-choco-500-g', 30]);
    }

    if ((int) $db->query('SELECT COUNT(*) FROM cms_section_items')->fetchColumn() === 0) {
        $insertItem = $db->prepare('INSERT INTO cms_section_items (section_key, icon, title, subtitle, sort_order) VALUES (?, ?, ?, ?, ?)');
        $items = [
            ['benefits', 'ph-plant', '100% Natural', 'Nothing artificial', 10],
            ['benefits', 'ph-orange-slice', 'Rich in Nutrients', 'Vitamins A, D, E & K', 20],
            ['benefits', 'ph-hexagon', 'Easy to Digest', 'A2 protein advantage', 30],
            ['benefits', 'ph-grains', 'Natural Fat Source', 'Goodness of ghee', 40],
            ['benefits', 'ph-cube', 'Daily Support', 'For a healthy lifestyle', 50],
            ['process', 'ph-cow', 'Free-Grazed', 'Gir Cows', 10],
            ['process', 'ph-drop', 'Fresh & Pure', 'A2 Milk Collection', 20],
            ['process', 'ph-bowl-food', 'Bilona Churning', 'in Small Batches', 30],
            ['process', 'ph-fire', 'Slow-Cooked', 'on Wood Fire', 40],
            ['process', 'ph-hands-praying', 'Handcrafted', 'with Care', 50],
            ['process', 'ph-jar', 'Pure Goodness', 'Every Jar', 60],
            ['assurance', 'ph-seal-check', '100% Natural', 'No additives', 10],
            ['assurance', 'ph-flask', 'Lab Tested', 'For purity & safety', 20],
            ['assurance', 'ph-fire', 'Bilona & Handcrafted', 'Slow process', 30],
            ['assurance', 'ph-heart', 'Full of Goodness', 'No shortcuts', 40],
            ['assurance', 'ph-plant', 'Sustainable', 'Good for our planet', 50],
            ['why', 'ph-cow', 'Free-Grazed Gir Cows', '', 10],
            ['why', 'ph-shield-check', 'No Preservatives', '', 20],
            ['why', 'ph-stomach', 'Easy A2 Digestion', '', 30],
            ['why', 'ph-fire', 'Bilona Churned', '', 40],
            ['why', 'ph-bowl-food', 'Small Batches', '', 50],
            ['why', 'ph-plant', 'Clean, Honest Products', '', 60],
            ['newsletter-perks', 'ph-seal-percent', 'Exclusive Offers', '', 10],
            ['newsletter-perks', 'ph-plant', 'Farm Updates', '', 20],
            ['newsletter-perks', 'ph-first-aid', 'Health Tips', '', 30],
        ];
        foreach ($items as $item) {
            $insertItem->execute($item);
        }
    }
}

function gawdee_seed_products(array $seedProducts): void
{
    $db = gawdee_db();
    $insert = $db->prepare(<<<'SQL'
INSERT OR IGNORE INTO products
(id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent)
VALUES (:id, :slug, :name, :full_name, :category, :category_key, :tag, :price, :original_price, :weight, :image, :description, :accent)
SQL);
    foreach ($seedProducts as $product) {
        $insert->execute($product);
    }
}

function gawdee_products(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM products' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY created_at, name';
    $rows = gawdee_db()->query($sql)->fetchAll();
    return array_map(static function (array $row): array {
        $row['price'] = (int) $row['price'];
        $row['original_price'] = (int) $row['original_price'];
        $row['stock'] = (int) $row['stock'];
        $row['rating'] = (float) ($row['rating'] ?? 0);
        $row['review_count'] = (int) ($row['review_count'] ?? 0);
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $rows);
}

function gawdee_product_by_id(string $id): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
    $statement->execute([$id]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    $row['price'] = (int) $row['price'];
    $row['original_price'] = (int) $row['original_price'];
    $row['stock'] = (int) ($row['stock'] ?? 0);
    $row['rating'] = (float) ($row['rating'] ?? 0);
    $row['review_count'] = (int) ($row['review_count'] ?? 0);
    return $row;
}

function gawdee_product_reviews(string $productId): array
{
    $statement = gawdee_db()->prepare("SELECT id, product_id, rating, review, name, created_at FROM product_reviews WHERE product_id = ? AND status = 'approved' ORDER BY id DESC");
    $statement->execute([$productId]);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = (int) $row['rating'];
        return $row;
    }, $statement->fetchAll());
}

function gawdee_all_product_reviews(): array
{
    $rows = gawdee_db()->query(<<<'SQL'
SELECT product_reviews.*, products.name AS product_name, products.slug AS product_slug
FROM product_reviews
LEFT JOIN products ON products.id = product_reviews.product_id
ORDER BY product_reviews.id DESC
SQL)->fetchAll();
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = min(5, max(1, (int) $row['rating']));
        return $row;
    }, $rows);
}

function gawdee_setting(string $key, string $default = ''): string
{
    $statement = gawdee_db()->prepare('SELECT setting_value, is_secret FROM settings WHERE setting_key = ?');
    $statement->execute([$key]);
    $row = $statement->fetch();
    if (!$row) {
        return $default;
    }
    $value = (int) $row['is_secret'] === 1 ? gawdee_decrypt((string) $row['setting_value']) : (string) $row['setting_value'];
    return $value;
}

function gawdee_set_setting(string $key, string $value, bool $secret = false): void
{
    $stored = $secret ? gawdee_encrypt($value) : $value;
    $statement = gawdee_db()->prepare(<<<'SQL'
INSERT INTO settings (setting_key, setting_value, is_secret, updated_at)
VALUES (?, ?, ?, CURRENT_TIMESTAMP)
ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, is_secret = excluded.is_secret, updated_at = CURRENT_TIMESTAMP
SQL);
    $statement->execute([$key, $stored, $secret ? 1 : 0]);
}

function gawdee_secret_key(): string
{
    $environmentKey = getenv('GAWDEE_APP_KEY');
    if (is_string($environmentKey) && $environmentKey !== '') {
        return hash('sha256', $environmentKey, true);
    }
    $path = GAWDEE_STORAGE . '/.app_key';
    if (!is_file($path)) {
        file_put_contents($path, base64_encode(random_bytes(32)), LOCK_EX);
        @chmod($path, 0600);
    }
    $decoded = base64_decode(trim((string) file_get_contents($path)), true);
    if ($decoded === false || strlen($decoded) < 32) {
        throw new RuntimeException('Application encryption key is invalid.');
    }
    return $decoded;
}

function gawdee_encrypt(string $plainText): string
{
    if ($plainText === '') {
        return '';
    }
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plainText, 'aes-256-gcm', gawdee_secret_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        throw new RuntimeException('Unable to encrypt the setting.');
    }
    return base64_encode(json_encode([
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'data' => base64_encode($cipher),
    ], JSON_THROW_ON_ERROR));
}

function gawdee_decrypt(string $payload): string
{
    if ($payload === '') {
        return '';
    }
    try {
        $decoded = json_decode((string) base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);
        $plain = openssl_decrypt(
            (string) base64_decode((string) $decoded['data'], true),
            'aes-256-gcm',
            gawdee_secret_key(),
            OPENSSL_RAW_DATA,
            (string) base64_decode((string) $decoded['iv'], true),
            (string) base64_decode((string) $decoded['tag'], true)
        );
        return $plain === false ? '' : $plain;
    } catch (Throwable) {
        return '';
    }
}

function gawdee_sections(): array
{
    $rows = gawdee_db()->query('SELECT * FROM cms_sections ORDER BY sort_order, section_key')->fetchAll();
    $sections = [];
    foreach ($rows as $row) {
        $row['is_active'] = (int) $row['is_active'];
        $sections[$row['section_key']] = $row;
    }
    return $sections;
}

function gawdee_section(string $key): array
{
    $sections = gawdee_sections();
    return $sections[$key] ?? ['section_key' => $key, 'eyebrow' => '', 'title' => '', 'subtitle' => '', 'body' => '', 'image' => '', 'mobile_image' => '', 'video_url' => '', 'button_label' => '', 'button_url' => '', 'is_active' => 1, 'sort_order' => 0];
}

function gawdee_banners(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM banners' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return gawdee_db()->query($sql)->fetchAll();
}

function gawdee_testimonials(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM testimonials' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = min(5, max(1, (int) $row['rating']));
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, gawdee_db()->query($sql)->fetchAll());
}

function gawdee_homepage_media(?string $sectionKey = null, bool $includeInactive = false): array
{
    $conditions = [];
    $values = [];
    if ($sectionKey !== null) {
        $conditions[] = 'section_key = ?';
        $values[] = $sectionKey;
    }
    if (!$includeInactive) {
        $conditions[] = 'is_active = 1';
    }
    $sql = 'SELECT * FROM homepage_media' . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY section_key, sort_order, id';
    $statement = gawdee_db()->prepare($sql);
    $statement->execute($values);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $statement->fetchAll());
}

function gawdee_video_testimonials(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM video_testimonials' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = min(5, max(1, (int) $row['rating']));
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, gawdee_db()->query($sql)->fetchAll());
}

function gawdee_section_items(?string $sectionKey = null, bool $includeInactive = false): array
{
    $conditions = [];
    $values = [];
    if ($sectionKey !== null) {
        $conditions[] = 'section_key = ?';
        $values[] = $sectionKey;
    }
    if (!$includeInactive) {
        $conditions[] = 'is_active = 1';
    }
    $sql = 'SELECT * FROM cms_section_items' . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY section_key, sort_order, id';
    $statement = gawdee_db()->prepare($sql);
    $statement->execute($values);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $statement->fetchAll());
}

function gawdee_has_admin(): bool
{
    return (int) gawdee_db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
}

function gawdee_admin(): ?array
{
    if (empty($_SESSION['admin_user_id'])) {
        return null;
    }
    $statement = gawdee_db()->prepare("SELECT id, name, email, role, last_login_at FROM users WHERE id = ? AND role = 'admin'");
    $statement->execute([(int) $_SESSION['admin_user_id']]);
    return $statement->fetch() ?: null;
}

function gawdee_require_admin(): array
{
    $admin = gawdee_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

function gawdee_customer(): ?array
{
    if (empty($_SESSION['customer_user_id'])) {
        return null;
    }
    $statement = gawdee_db()->prepare("SELECT id, name, email, role, phone, address1, address2, city, state, pincode, whatsapp_marketing_opt_in, whatsapp_marketing_opt_in_at, whatsapp_opt_out_at, created_at, last_login_at FROM users WHERE id = ? AND role = 'customer'");
    $statement->execute([(int) $_SESSION['customer_user_id']]);
    return $statement->fetch() ?: null;
}

function gawdee_require_customer(string $returnTo = 'account.php'): array
{
    $customer = gawdee_customer();
    if ($customer) {
        return $customer;
    }
    header('Location: login.php?return=' . rawurlencode(gawdee_safe_return_path($returnTo)));
    exit;
}

function gawdee_safe_return_path(string $path, string $fallback = 'account.php'): string
{
    $path = trim($path);
    if (!preg_match('~^(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\\.php(?:[?#][^\\x00-\\x20\\\\]*)?$~D', $path)) {
        return $fallback;
    }
    return ltrim($path, '/');
}

function gawdee_customer_orders(int $userId): array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function gawdee_customer_order(int $userId, string $orderNumber): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE user_id = ? AND order_number = ?');
    $statement->execute([$userId, $orderNumber]);
    $order = $statement->fetch();
    if (!$order) {
        return null;
    }
    $items = gawdee_db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $items->execute([(int) $order['id']]);
    $events = gawdee_db()->prepare('SELECT * FROM order_status_events WHERE order_id = ? ORDER BY id');
    $events->execute([(int) $order['id']]);
    $order['items'] = $items->fetchAll();
    $order['events'] = $events->fetchAll();
    return $order;
}

function gawdee_record_order_event(int $orderId, string $status, string $title, string $description = ''): void
{
    $statement = gawdee_db()->prepare('INSERT INTO order_status_events (order_id, status, title, description) VALUES (?, ?, ?, ?)');
    $statement->execute([$orderId, $status, $title, $description]);
}

function gawdee_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf_token'];
}

function gawdee_verify_csrf(?string $token): void
{
    if (!$token || !hash_equals(gawdee_csrf_token(), $token)) {
        throw new RuntimeException('Your session expired. Refresh the page and try again.');
    }
}

function gawdee_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'post-' . date('Ymd-His');
}

function gawdee_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function gawdee_request_json(): array
{
    $payload = json_decode((string) file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function gawdee_log_integration(string $integration, string $action, string $status, string $message = '', string $reference = ''): void
{
    $statement = gawdee_db()->prepare('INSERT INTO integration_logs (integration, action, status, reference, message) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$integration, $action, $status, $reference, mb_substr($message, 0, 1500)]);
}

function gawdee_mark_order_paid(int $orderId, string $paymentId = '', string $signature = ''): void
{
    $db = gawdee_db();
    $becamePaid = false;
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT payment_status, inventory_status FROM orders WHERE id = ?');
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['payment_status'] !== 'paid') {
            if ($order['inventory_status'] !== 'reserved' && $order['inventory_status'] !== 'deducted') {
                $items = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $items->execute([$orderId]);
                $orderItems = $items->fetchAll();
                foreach ($orderItems as $item) {
                    $stock = $db->prepare('SELECT stock FROM products WHERE id = ?');
                    $stock->execute([$item['product_id']]);
                    if ((int) $stock->fetchColumn() < (int) $item['quantity']) {
                        $db->prepare("UPDATE orders SET payment_status='paid', status='on_hold', payment_error='Payment received, but stock needs manual review.', paid_at=CURRENT_TIMESTAMP, cancelled_at=NULL, razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                            ->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
                        gawdee_record_order_event($orderId, 'on_hold', 'Payment received — stock review needed', 'Payment is secure, but fulfilment needs an inventory check by the store team.');
                        $db->commit();
                        if (function_exists('gawdee_queue_order_notification')) {
                            gawdee_queue_order_notification($orderId, 'payment_confirmed');
                        }
                        return;
                    }
                }
                $reduce = $db->prepare("UPDATE products SET stock = stock - ?, stock_status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE 'in_stock' END WHERE id = ?");
                foreach ($orderItems as $item) {
                    $reduce->execute([(int) $item['quantity'], (int) $item['quantity'], $item['product_id']]);
                }
            }
            $update = $db->prepare("UPDATE orders SET payment_status='paid', status='processing', shipment_status='awaiting_fulfillment', inventory_status='deducted', payment_error='', paid_at=CURRENT_TIMESTAMP, cancelled_at=NULL, razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $update->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
            gawdee_record_order_event($orderId, 'processing', 'Payment confirmed', 'Secure online payment was verified and the order moved to processing.');
            $becamePaid = true;
        } elseif ($paymentId !== '' || $signature !== '') {
            $update = $db->prepare("UPDATE orders SET razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $update->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
    if ($becamePaid && function_exists('gawdee_queue_order_notification')) {
        gawdee_queue_order_notification($orderId, 'payment_confirmed');
        gawdee_queue_order_notification($orderId, 'order_confirmed');
    }
}

function gawdee_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    return $scheme . '://' . $host;
}

gawdee_db();
