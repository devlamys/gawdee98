<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/integrations.php';

$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
    if (!$condition) {
        $failures[] = $label;
    }
};

$check(count(gawdee_products()) >= 8, 'seeded product catalogue');
$check(count(gawdee_banners()) >= 3, 'seeded homepage banners');
$check(count(gawdee_sections()) >= 10, 'homepage CMS sections');
$check(count(gawdee_testimonials()) >= 4, 'homepage testimonial CMS');
$check(count(gawdee_homepage_media('reels')) >= 3, 'homepage media CMS');
$offerSection = gawdee_section('offer');
$check($offerSection['image'] !== '' && $offerSection['mobile_image'] !== '', 'CMS-managed offer artwork');
$blogColumns = array_column(gawdee_db()->query('PRAGMA table_info(blog_posts)')->fetchAll(), 'name');
$check(in_array('featured_image', $blogColumns, true) && in_array('is_featured', $blogColumns, true), 'image-led blog publishing schema');

gawdee_set_setting('qa_secret', 'temporary-secret', true);
$statement = gawdee_db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
$statement->execute(['qa_secret']);
$stored = (string) $statement->fetchColumn();
$check($stored !== 'temporary-secret' && gawdee_setting('qa_secret') === 'temporary-secret', 'encrypted setting round-trip');
gawdee_db()->prepare('DELETE FROM settings WHERE setting_key = ?')->execute(['qa_secret']);

gawdee_set_setting('razorpay_key_secret', 'qa-secret', true);
$signature = hash_hmac('sha256', 'order_qa|pay_qa', 'qa-secret');
$check(gawdee_razorpay_verify_payment('order_qa', 'pay_qa', $signature), 'Razorpay signature verification');
$check(!gawdee_razorpay_verify_payment('order_qa', 'pay_wrong', $signature), 'Razorpay signature rejection');
gawdee_db()->prepare('DELETE FROM settings WHERE setting_key = ?')->execute(['razorpay_key_secret']);

$order = [
    'order_number' => 'GDQA', 'customer_name' => 'QA', 'email' => 'qa@example.test', 'phone' => '9999999999',
    'address1' => 'Address', 'address2' => '', 'city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110001',
    'total' => 100, 'payment_method' => 'cod',
];
$payload = gawdee_dtdc_apply_template(
    '{"reference":"{{order_number}}","pincode":"{{pincode}}","items":{{items_json}}}',
    $order,
    [['product_id' => 'mixme-choco', 'quantity' => 1]]
);
$check($payload['reference'] === 'GDQA' && count($payload['items']) === 1, 'DTDC custom payload mapping');

$dirty = '<h2 onclick="bad()">Good</h2><script>alert(1)</script><p style="color:red">Clean</p>';
$clean = gawdee_sanitize_article_html($dirty);
$check(!str_contains($clean, '<script') && !str_contains($clean, 'onclick') && !str_contains($clean, 'style='), 'blog HTML sanitization');

exit($failures ? 1 : 0);
